<?php
// Ensure snake DB schema on page load (used by index.php fetches)
if (!function_exists('snakeEnsureSchema')) {
    function snakeEnsureSchema(mysqli $conn): bool
    {
        static $done = false;
        if ($done) {
            return true;
        }
        $done = true;

        try {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
            );
            if (!$stmt) {
                error_log('snake: schema check prepare failed - ' . $conn->error);
                return false;
            }
            $table = 'snake_scores';
            $stmt->bind_param('s', $table);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();
            if ($row && (int) $row['cnt'] > 0) {
                return true;
            }

            $migrationPaths = [
                '/www/fun/snake/dbinit/0001_init.sql',
                __DIR__ . '/../dbinit/0001_init.sql',
            ];
            foreach ($migrationPaths as $path) {
                if (file_exists($path)) {
                    $sql = file_get_contents($path);
                    if ($sql === false) {
                        error_log('snake: dbinit read failed - ' . $path);
                        return false;
                    }
                    if (!$conn->multi_query($sql)) {
                        error_log('snake: dbinit failed - ' . $conn->error);
                        return false;
                    }
                    do {
                        if ($r = $conn->store_result()) {
                            $r->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());
                    break;
                }
            }
        } catch (Throwable $e) {
            error_log('snake: db ensure failed - ' . $e->getMessage());
            return false;
        }
        return true;
    }
}
