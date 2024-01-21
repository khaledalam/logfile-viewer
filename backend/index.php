<?php

header("Access-Control-Allow-Origin: *");

require_once 'FileHandler.php';

/**
 * class BasicAuth
 *
 * Handle basic access validation.
 */
class BasicAuth {
    private string $user = 'admin';
    private string $pass = 'admin';

    /**
     * website validation.
     *
     * @return void
     */
    public function handleWeb(): void
    {
        header('WWW-Authenticate: Basic realm="My Realm"');

        // Click cancel button in prompt popup.
        if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            echo $this->getUnauthorizedMessage();
            exit;
        }

        // Wrong username or password.
        if ($_SERVER['PHP_AUTH_USER'] !== $this->user || $_SERVER['PHP_AUTH_PW'] !== $this->pass) {
            echo $this->getUnauthorizedMessage();
            exit;
        }
    }

    /**
     * validate basic auth in frontend.
     *
     * @return void
     */
    public function handleUiAuth(): void
    {
        // wrong username or password.
        if ($_GET['auth']['username'] !== $this->user || $_GET['auth']['password'] !== $this->pass) {
            echo $this->getUnauthorizedMessage();
            exit;
        }
    }

    /**
     * xhr validation.
     *
     * @return void
     */
    public function handleAxios(): void
    {
        // wrong username or password.
        if ($_GET['auth']['username'] !== $this->user || $_GET['auth']['password'] !== $this->pass) {
            echo $this->getUnauthorizedMessage();
            exit;
        }
        header('HTTP/1.0 200 OK'); 
    }

    /**
     * @TODO: Change Unauthorized message.
     *
     * Message that user get when try to access page and click cancel button in prompt popup.
     *
     * @return string
     */
    private function getUnauthorizedMessage(): string
    {
        header('HTTP/1.0 401 Unauthorized');

        return <<<HTML
            <h3 style="text-align: center; color: red;">Unauthorized.</h3>
        HTML;
    }
}


if (isset($_GET['check_auth'])) {
    (new BasicAuth())->handleUiAuth();
    exit;
}
else if (isset($_GET['auth']['username'], $_GET['auth']['password'])) {
    (new BasicAuth())->handleAxios();

} else {
    (new BasicAuth())->handleWeb();
}

(new FileHandler())->init();
