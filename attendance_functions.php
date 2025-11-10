<?php
class EnhancedAttendanceHandler {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get comprehensive class statistics
    public function getEnhancedClassStats($year, $section, $instructor_id) {
        $stats = [];
        $today = date('Y-m-d');
        
        // Get present students with time details
        $present_query = "SELECT 
                            COUNT(DISTINCT al.student_id) as present_count,
                            MIN(TIME(al.time_in)) as earliest_time,
                            MAX(TIME(al.time_in)) as latest_time,
                            AVG(TIME_TO_SEC(TIME(al.time_in))) as avg_time_sec,
                            SUM(CASE WHEN TIME(al.time_in) > '08:30:00' THEN 1 ELSE 0 END) as late_count
                         FROM attendance_logs al
                         JOIN students s ON al.student_id = s.id
                         WHERE s.section = ? 
                         AND s.year = ?
                         AND DATE(al.time_in) = ?
                         AND al.instructor_id = ?";
        
        $stmt = $this->db->prepare($present_query);
        if ($stmt) {
            $stmt->bind_param("sssi", $section, $year, $today, $instructor_id);
            $stmt->execute();
            $stats['present'] = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        // Get total students in class
        $total_query = "SELECT COUNT(*) as total_count 
                       FROM students 
                       WHERE section = ? AND year = ? AND status = 'active'";
        
        $stmt = $this->db->prepare($total_query);
        if ($stmt) {
            $stmt->bind_param("ss", $section, $year);
            $stmt->execute();
            $stats['total'] = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        // Calculate additional statistics
        $stats['absent_count'] = ($stats['total']['total_count'] ?? 0) - ($stats['present']['present_count'] ?? 0);
        $stats['late_count'] = $stats['present']['late_count'] ?? 0;
        $stats['on_time_count'] = ($stats['present']['present_count'] ?? 0) - ($stats['present']['late_count'] ?? 0);
        
        // Calculate percentages
        if (($stats['total']['total_count'] ?? 0) > 0) {
            $stats['attendance_rate'] = round(($stats['present']['present_count'] / $stats['total']['total_count']) * 100, 2);
            $stats['punctuality_rate'] = $stats['present']['present_count'] > 0 ? 
                round(($stats['on_time_count'] / $stats['present']['present_count']) * 100, 2) : 0;
        } else {
            $stats['attendance_rate'] = 0;
            $stats['punctuality_rate'] = 0;
        }
        
        return $stats;
    }
    
    // Enhanced function to save attendance with room_id and subject_id
    public function saveEnhancedAttendance($db, $classmates, $instructor_id, $year, $section) {
        $today = date('Y-m-d');
        $current_semester = $this->getCurrentSemester();
        $current_academic_year = $this->getCurrentAcademicYear();
        
        // Get room_id and subject_id from session
        $room_id = $this->getRoomId($db, $_SESSION['access']['room']['room'] ?? '');
        $subject_id = $this->getSubjectId($db, $_SESSION['access']['subject']['name'] ?? '');
        
        foreach ($classmates as $student) {
            // Get student's attendance details
            $attendance_details = $this->getStudentAttendanceDetails($db, $student['id_number'], $today, $instructor_id);
            
            // Determine attendance status
            $status = $attendance_details['is_present'] ? 
                     ($attendance_details['punctuality'] === 'Late' ? 'Late' : 'Present') : 
                     'Absent';
            
            // Check if record already exists
            $check_query = "SELECT id FROM instructor_attendance_records 
                           WHERE instructor_id = ? 
                           AND student_id_number = ? 
                           AND date = ? 
                           AND year = ? 
                           AND section = ?";
            
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bind_param("issss", $instructor_id, $student['id_number'], $today, $year, $section);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert new enhanced record
                $insert_query = "INSERT INTO instructor_attendance_records 
                               (instructor_id, student_id_number, student_name, section, year, 
                                department, status, date, subject_id, room_id, semester, 
                                academic_year, time_in, time_out, remarks, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $insert_stmt = $db->prepare($insert_query);
                
                $insert_stmt->bind_param(
                    "isssssssiisssss", 
                    $instructor_id, 
                    $student['id_number'],
                    $student['fullname'],
                    $section,
                    $year,
                    $student['department_name'],
                    $status,
                    $today,
                    $subject_id,
                    $room_id,
                    $current_semester,
                    $current_academic_year,
                    $attendance_details['time_in'],
                    $attendance_details['time_out'],
                    $attendance_details['remarks']
                );
                
                $insert_stmt->execute();
                $insert_stmt->close();
            } else {
                // Update existing record with enhanced data
                $update_query = "UPDATE instructor_attendance_records 
                               SET status = ?, subject_id = ?, room_id = ?, semester = ?, 
                                   academic_year = ?, time_in = ?, time_out = ?, remarks = ?, 
                                   updated_at = NOW() 
                               WHERE instructor_id = ? 
                               AND student_id_number = ? 
                               AND date = ? 
                               AND year = ? 
                               AND section = ?";
                
                $update_stmt = $db->prepare($update_query);
                
                $update_stmt->bind_param(
                    "siisssssissss", 
                    $status,
                    $subject_id,
                    $room_id,
                    $current_semester,
                    $current_academic_year,
                    $attendance_details['time_in'],
                    $attendance_details['time_out'],
                    $attendance_details['remarks'],
                    $instructor_id, 
                    $student['id_number'],
                    $today,
                    $year,
                    $section
                );
                
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            $check_stmt->close();
        }
        
        return true;
    }
    
    private function getStudentAttendanceDetails($db, $student_id, $date, $instructor_id) {
        $query = "SELECT 
                    time_in, 
                    time_out,
                    CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END as is_present,
                    CASE WHEN TIME(time_in) > '08:30:00' THEN 'Late' ELSE 'On Time' END as punctuality
                 FROM attendance_logs 
                 WHERE id_number = ? 
                 AND DATE(time_in) = ? 
                 AND instructor_id = ?
                 ORDER BY time_in DESC 
                 LIMIT 1";
        
        $stmt = $db->prepare($query);
        $details = [
            'time_in' => null,
            'time_out' => null,
            'is_present' => false,
            'punctuality' => 'Absent',
            'remarks' => ''
        ];
        
        if ($stmt) {
            $stmt->bind_param("ssi", $student_id, $date, $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $details = [
                    'time_in' => $data['time_in'],
                    'time_out' => $data['time_out'],
                    'is_present' => (bool)$data['is_present'],
                    'punctuality' => $data['punctuality'],
                    'remarks' => $data['punctuality'] == 'Late' ? 'Arrived after class start time' : 'Present'
                ];
            }
            $stmt->close();
        }
        
        return $details;
    }
    
    private function getRoomId($db, $room_name) {
        $stmt = $db->prepare("SELECT id FROM rooms WHERE room = ?");
        if ($stmt) {
            $stmt->bind_param("s", $room_name);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
        }
        return null;
    }
    
    private function getSubjectId($db, $subject_name) {
        $stmt = $db->prepare("SELECT id FROM subjects WHERE subject_name = ?");
        if ($stmt) {
            $stmt->bind_param("s", $subject_name);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
        }
        return null;
    }
    
    private function getCurrentSemester() {
        $month = date('n');
        if ($month >= 1 && $month <= 5) return '2nd Semester';
        if ($month >= 8 && $month <= 12) return '1st Semester';
        return 'Summer';
    }
    
    private function getCurrentAcademicYear() {
        $year = date('Y');
        $month = date('n');
        if ($month >= 8) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }
    
    // Get comprehensive report for display
    public function getEnhancedAttendanceReport($instructor_id, $date = null) {
        $date = $date ?: date('Y-m-d');
        
        $query = "SELECT 
                    iar.*,
                    r.room as room_name,
                    s.subject_name,
                    s.subject_code
                 FROM instructor_attendance_records iar
                 LEFT JOIN rooms r ON iar.room_id = r.id
                 LEFT JOIN subjects s ON iar.subject_id = s.id
                 WHERE iar.instructor_id = ? AND iar.date = ?
                 ORDER BY iar.section, iar.student_name";
        
        $stmt = $this->db->prepare($query);
        $report = [];
        
        if ($stmt) {
            $stmt->bind_param("is", $instructor_id, $date);
            $stmt->execute();
            $report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        
        return $report;
    }
    
    // Get class summary for dashboard
    public function getClassSummary($instructor_id) {
        $today = date('Y-m-d');
        
        $query = "SELECT 
                    section,
                    year,
                    COUNT(*) as total_students,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
                    ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
                 FROM instructor_attendance_records 
                 WHERE instructor_id = ? AND date = ?
                 GROUP BY section, year
                 LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $summary = null;
        
        if ($stmt) {
            $stmt->bind_param("is", $instructor_id, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $summary = $result->fetch_assoc();
            $stmt->close();
        }
        
        return $summary;
    }
}
?>