<?php
/**
 * Query Optimizer Class
 * Provides optimized database queries and caching for Marina Hotel system
 */

class QueryOptimizer {
    private $conn;
    private $cache = [];
    private $cache_ttl = 300; // 5 minutes default cache
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get optimized booking list with payments
     * Replaces the N+1 query problem in list.php
     */
    public function getOptimizedBookingList($status_filter = null) {
        $cache_key = 'booking_list_' . md5($status_filter ?? 'all');
        
        // Check cache first
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        $where_clause = "";
        if ($status_filter) {
            $where_clause = "AND b.status = '" . $this->conn->real_escape_string($status_filter) . "'";
        }
        
        // Optimized query with JOINs instead of subqueries
        $query = "
            SELECT 
                b.booking_id,
                b.guest_name,
                b.guest_phone,
                b.room_number,
                r.price AS room_price,
                r.type AS room_type,
                DATE_FORMAT(b.checkin_date, '%d/%m/%Y') AS checkin_date,
                b.calculated_nights,
                COALESCE(p.total_paid, 0) AS paid_amount,
                (r.price * b.calculated_nights) - COALESCE(p.total_paid, 0) AS remaining_amount,
                b.status,
                b.notes,
                COALESCE(bn.alert_count, 0) AS has_alerts,
                b.last_calculation
            FROM bookings b
            JOIN rooms r ON b.room_number = r.room_number
            LEFT JOIN (
                SELECT booking_id, SUM(amount) as total_paid 
                FROM payment 
                GROUP BY booking_id
            ) p ON b.booking_id = p.booking_id
            LEFT JOIN (
                SELECT booking_id, COUNT(*) as alert_count 
                FROM booking_notes 
                WHERE is_active = 1 AND (alert_until IS NULL OR alert_until > NOW())
                GROUP BY booking_id
            ) bn ON b.booking_id = bn.booking_id
            WHERE b.actual_checkout IS NULL $where_clause
            ORDER BY b.checkin_date DESC
        ";
        
        $result = $this->conn->query($query);
        $bookings = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
        }
        
        // Cache the results
        $this->setCache($cache_key, $bookings);
        
        return $bookings;
    }
    
    /**
     * Get dashboard statistics with caching
     */
    public function getDashboardStats() {
        $cache_key = 'dashboard_stats';
        
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        // Single query to get all dashboard stats
        $query = "
            SELECT 
                (SELECT COUNT(*) FROM bookings WHERE status = 'محجوزة' AND actual_checkout IS NULL) as occupied_rooms,
                (SELECT COUNT(*) FROM rooms WHERE status = 'شاغرة') as available_rooms,
                (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) = CURDATE()) as today_checkins,
                (SELECT COALESCE(SUM(amount), 0) FROM payment WHERE DATE(payment_date) = CURDATE()) as today_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE date = CURDATE()) as today_expenses,
                (SELECT COUNT(*) FROM booking_notes WHERE is_active = 1 AND alert_type = 'high') as high_alerts
        ";
        
        $result = $this->conn->query($query);
        $stats = $result ? $result->fetch_assoc() : [];
        
        // Cache for 2 minutes (dashboard updates frequently)
        $this->setCache($cache_key, $stats, 120);
        
        return $stats;
    }
    
    /**
     * Get payment history for a booking with optimization
     */
    public function getBookingPayments($booking_id) {
        $cache_key = 'booking_payments_' . $booking_id;
        
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        $query = "
            SELECT 
                p.payment_id,
                p.amount,
                DATE_FORMAT(p.payment_date, '%d/%m/%Y %H:%i') as payment_date,
                p.payment_method,
                p.revenue_type,
                p.notes,
                ct.description as transaction_description
            FROM payment p
            LEFT JOIN cash_transactions ct ON p.cash_transaction_id = ct.id
            WHERE p.booking_id = ?
            ORDER BY p.payment_date DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        $stmt->close();
        
        // Cache for 5 minutes
        $this->setCache($cache_key, $payments);
        
        return $payments;
    }
    
    /**
     * Get room occupancy report with optimization
     */
    public function getRoomOccupancyReport($start_date, $end_date) {
        $cache_key = 'occupancy_report_' . md5($start_date . $end_date);
        
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        $query = "
            SELECT 
                r.room_number,
                r.type,
                r.price,
                COUNT(b.booking_id) as total_bookings,
                SUM(b.calculated_nights) as total_nights,
                COALESCE(SUM(p.amount), 0) as total_revenue,
                AVG(b.calculated_nights) as avg_stay_duration
            FROM rooms r
            LEFT JOIN bookings b ON r.room_number = b.room_number 
                AND b.checkin_date BETWEEN ? AND ?
            LEFT JOIN payment p ON b.booking_id = p.booking_id
            GROUP BY r.room_number, r.type, r.price
            ORDER BY total_revenue DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $report = [];
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }
        
        $stmt->close();
        
        // Cache for 10 minutes (reports don't change frequently)
        $this->setCache($cache_key, $report, 600);
        
        return $report;
    }
    
    /**
     * Get financial summary with optimization
     */
    public function getFinancialSummary($start_date, $end_date) {
        $cache_key = 'financial_summary_' . md5($start_date . $end_date);
        
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        $query = "
            SELECT 
                'revenue' as type,
                DATE(payment_date) as date,
                SUM(amount) as amount,
                COUNT(*) as count
            FROM payment 
            WHERE payment_date BETWEEN ? AND ?
            GROUP BY DATE(payment_date)
            
            UNION ALL
            
            SELECT 
                'expense' as type,
                date,
                SUM(amount) as amount,
                COUNT(*) as count
            FROM expenses 
            WHERE date BETWEEN ? AND ?
            GROUP BY date
            
            ORDER BY date DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $summary = [];
        while ($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }
        
        $stmt->close();
        
        // Cache for 10 minutes
        $this->setCache($cache_key, $summary, 600);
        
        return $summary;
    }
    
    /**
     * Search guests with optimization
     */
    public function searchGuests($search_term) {
        $cache_key = 'guest_search_' . md5($search_term);
        
        if ($this->isCached($cache_key)) {
            return $this->getFromCache($cache_key);
        }
        
        $search_term = '%' . $this->conn->real_escape_string($search_term) . '%';
        
        $query = "
            SELECT DISTINCT
                guest_name,
                guest_phone,
                guest_id_number,
                guest_nationality,
                COUNT(booking_id) as total_bookings,
                MAX(checkin_date) as last_visit
            FROM bookings 
            WHERE guest_name LIKE ? 
               OR guest_phone LIKE ? 
               OR guest_id_number LIKE ?
            GROUP BY guest_name, guest_phone, guest_id_number, guest_nationality
            ORDER BY last_visit DESC
            LIMIT 20
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sss', $search_term, $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $guests = [];
        while ($row = $result->fetch_assoc()) {
            $guests[] = $row;
        }
        
        $stmt->close();
        
        // Cache for 5 minutes
        $this->setCache($cache_key, $guests);
        
        return $guests;
    }
    
    /**
     * Cache management methods
     */
    private function isCached($key) {
        return isset($this->cache[$key]) && 
               $this->cache[$key]['expires'] > time();
    }
    
    private function getFromCache($key) {
        return $this->cache[$key]['data'];
    }
    
    private function setCache($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->cache_ttl;
        $this->cache[$key] = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
    }
    
    /**
     * Clear cache for specific key or all cache
     */
    public function clearCache($key = null) {
        if ($key) {
            unset($this->cache[$key]);
        } else {
            $this->cache = [];
        }
    }
    
    /**
     * Log slow queries for monitoring
     */
    public function logSlowQuery($query, $execution_time, $rows_examined = null, $rows_sent = null) {
        if ($execution_time > 1.0) { // Log queries taking more than 1 second
            $log_query = "
                INSERT INTO slow_queries 
                (query_text, execution_time, rows_examined, rows_sent, user, host) 
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->conn->prepare($log_query);
            $user = $_SESSION['username'] ?? 'unknown';
            $host = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt->bind_param('sdiiis', $query, $execution_time, $rows_examined, $rows_sent, $user, $host);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Record performance metrics
     */
    public function recordMetric($metric_name, $metric_value, $details = null) {
        $query = "INSERT INTO performance_metrics (metric_name, metric_value, details) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sds', $metric_name, $metric_value, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>