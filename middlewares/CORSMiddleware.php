<?php

/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CORSMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    const PRIORITY = 1;

    /**
     * @var array
     */
    protected $corsHeaders = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type',
        'Access-Control-Allow-Methods' => 'PUT, GET, POST, DELETE, OPTIONS',
        'Access-Control-Allow-Credentials' => 'true'
    ];

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * CatchExceptionMiddleware constructor.
     *
     * @param HttpKernelInterface $app
     */
    public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($request->getMethod() === 'OPTIONS' && $request->headers->has('Access-Control-Request-Headers')) {
            foreach ($this->corsHeaders as $header => $value) {
                header("$header: $value");
            }
            exit();
        }

        $response = $this->app->handle($request, $type, $catch);

        if ($request->isXmlHttpRequest()) {
            foreach ($this->corsHeaders as $header => $value) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
