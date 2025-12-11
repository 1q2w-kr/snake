<?php
// Ensure snake DB schema on page load (used by index.php fetches)
if (!function_exists('snakeEnsureSchema')) {
    function snakeEnsureSchema(mysqli $conn): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $result = $conn->query("SHOW TABLES LIKE 'snake_scores'");
            if ($result && $result->num_rows > 0) {
                return;
            }

            $migrationPaths = [
                '/www/fun/snake/db/migrations/0001_init.sql',
                __DIR__ . '/../db/migrations/0001_init.sql',
            ];
            foreach ($migrationPaths as $path) {
                if (file_exists($path)) {
                    $sql = file_get_contents($path);
                    if ($sql !== false && $conn->multi_query($sql)) {
                        do {
                            if ($r = $conn->store_result()) {
                                $r->free();
                            }
                        } while ($conn->more_results() && $conn->next_result());
                    }
                    break;
                }
            }
        } catch (Throwable $e) {
            error_log('snake: db ensure failed - ' . $e->getMessage());
        }
    }
}
