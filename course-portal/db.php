<?php
/**
 * db.php  –  Database Configuration Class
 */

class Database
{
    // Change these four values to match your environment
    private const HOST     = "localhost";
    private const DB_USER  = "root";
    private const DB_PASS  = "";
    private const DB_NAME  = "course_portal";

    /** Singleton connection instance */
    private static $instance = null;

    /**
     * Returns the shared mysqli connection.
     * Creates it on the first call; reuses it on every subsequent call.
     */
    public static function getConnection()
    {
        if (self::$instance === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $conn = new mysqli(self::HOST, self::DB_USER, self::DB_PASS, self::DB_NAME);
                $conn->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e) {
                // Friendly error - never expose raw messages to the browser in production
                die("
                <div style='font-family:sans-serif;max-width:560px;margin:60px auto;
                            padding:30px;border:1px solid #f5c6cb;border-radius:6px;
                            background:#fff3f3;color:#721c24;'>
                    <h3 style='margin-top:0'>&#9888; Database Connection Error</h3>
                    <p>The application could not connect to the database.<br>
                       Please verify the credentials in <code>db.php</code> and
                       ensure your MySQL server is running.</p>
                </div>");
            }

            self::$instance = $conn;
        }

        return self::$instance;
    }

    /** Prevent direct instantiation — this class is used statically only */
    private function __construct() {}
}
