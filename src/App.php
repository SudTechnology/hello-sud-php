<?php
namespace SudGipAuth;

use Sud\Gip\Api\SudGIPAuth;
use Sud\Gip\Api\RequestSigner;
use SudGipAuth\Routes\Router;
use SudGipAuth\Http\CallbackSignature;
use SudGipAuth\Http\Response;

class App
{
    private $auth;
    private RequestSigner $requestSigner;
    private $router;
    private $appId;
    private $secret;
    private $verifyCallbacks;

    public function __construct()
    {
        $this->appId = self::env('APP_ID');
        $this->secret = self::env('APP_SECRET');
        $flag = filter_var(self::env('VERIFY_CALLBACK_SIGNATURE', 'false'),
            FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($flag === null) {
            throw new \InvalidArgumentException('Invalid VERIFY_CALLBACK_SIGNATURE setting');
        }
        $this->verifyCallbacks = $flag;
        $this->auth = new SudGIPAuth($this->appId, $this->secret);
        $this->requestSigner = new RequestSigner($this->appId, $this->secret);
        $this->router = new Router();
        $routes = require __DIR__ . '/Routes/routes.php';
        $routes($this->router, $this);
    }

    private static function env(string $name, string $default = ''): string
    {
        $value = getenv($name);
        return $value !== false ? $value : ($_ENV[$name] ?? $default);
    }

    public function run(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Read once, before parsing JSON or dispatching to any business handler.
        // Do not transform these bytes: verification must cover exactly the received entity body.
        $originalBody = file_get_contents('php://input');
        if ($originalBody === false) {
            throw new \RuntimeException('Cannot read request body');
        }
        $protected = $path === '/api' || str_starts_with($path, '/api/');
        if ($this->verifyCallbacks && $protected && $path !== '/api/getCode'
            && !CallbackSignature::verify($_SERVER, $originalBody, $this->requestSigner)) {
            Response::error(403, 'Invalid callback signature');
            return;
        }
        // The router may deserialize the same, unchanged bytes only after successful verification.
        $this->router->dispatch($originalBody);
    }

    public function getAuthService(): SudGIPAuth
    {
        return $this->auth;
    }
}
