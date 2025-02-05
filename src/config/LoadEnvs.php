<?php
class LoadEnv {

    public static function load($envs) {
        try {
            foreach ($envs as $env) {
                $filePath = CONFIG . 'envs/' . $env;
                if (!file_exists($filePath)) {
                    throw new Exception("Environment file not found: $filePath");
                }

                $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Skip comments and empty lines
                    if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                        continue;
                    }

                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);

                    if (!empty($name)) {
                        $_ENV[$name] = $value;
                    }
                }
            }
        } catch (Throwable $th) {
            error_log($th->getMessage());
        }
    }

    public static function set($name, $value) {
        $filePath = CONFIG . 'envs/.admin.env';
        if (!file_exists($filePath)) {
            throw new Exception("Environment file not found: $filePath");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $updated = false;

        foreach ($lines as &$line) {
            if (strpos(trim($line), '#') === 0 || trim($line) === '') {
            continue;
            }

            list($envName, $envValue) = explode('=', $line, 2);
            $envName = trim($envName);

            if ($envName === $name) {
            $line = "$name=$value";
            $updated = true;
            break;
            }
        }

        if (!$updated) {
            $lines[] = "$name=$value";
        }

        file_put_contents($filePath, implode(PHP_EOL, $lines));
        $_ENV[$name] = $value;
    }
}
?>