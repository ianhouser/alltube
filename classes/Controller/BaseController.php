<?php

/**
 * BaseController class.
 */

namespace Alltube\Controller;

use Alltube\Config;
use Alltube\Library\Downloader;
use Alltube\Library\Video;
use Alltube\LocaleManager;
use Alltube\SessionManager;
use Aura\Session\Segment;
use Consolidation\Log\Logger;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Abstract class used by every controller.
 */
abstract class BaseController
{
    /**
     * Current video.
     *
     * @var Video
     */
    protected $video;

    /**
     * Default youtube-dl format.
     *
     * @var string
     */
    protected $defaultFormat = 'best/bestvideo';

    /**
     * Slim dependency container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Config instance.
     *
     * @var Config
     */
    protected $config;

    /**
     * Session segment used to store session variables.
     *
     * @var Segment
     */
    protected $sessionSegment;

    /**
     * LocaleManager instance.
     *
     * @var LocaleManager
     */
    protected $localeManager;

    /**
     * Downloader instance.
     *
     * @var Downloader
     */
    protected $downloader;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * BaseController constructor.
     *
     * @param ContainerInterface $container Slim dependency container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('config');
        $this->container = $container;
        $session = SessionManager::getSession();
        $this->sessionSegment = $session->getSegment(self::class);
        $this->localeManager = $this->container->get('locale');
        $this->downloader = $this->config->getDownloader();
        $this->logger = $this->container->get('logger');
        $this->downloader->setLogger($this->logger);

        if (!$this->config->stream) {
            // Force HTTP if stream is not enabled.
            $this->defaultFormat = Config::addHttpToFormat($this->defaultFormat);
        }
    }

    /**
     * Get video format from request parameters or default format if none is specified.
     *
     * @param Request $request PSR-7 request
     *
     * @return string format
     */
    protected function getFormat(Request $request)
    {
        $format = $request->getQueryParam('format');
        if (!isset($format)) {
            $format = $this->defaultFormat;
        }

        return $format;
    }

    /**
     * Get the password entered for the current video.
     *
     * @param Request $request PSR-7 request
     *
     * @return string Password
     */
    protected function getPassword(Request $request)
    {
        $url = $request->getQueryParam('url');

        $password = $request->getParam('password');
        if (isset($password)) {
            $this->sessionSegment->setFlash($url, $password);
        } else {
            $password = $this->sessionSegment->getFlash($url);
        }

        return $password;
    }

    /**
     * Display an user-friendly error.
     *
     * @param Request $request PSR-7 request
     * @param Response $response PSR-7 response
     * @param string $message Error message
     *
     * @return Response HTTP response
     */
    protected function displayError(Request $request, Response $response, string $message)
    {
        $controller = new FrontController($this->container);

        return $controller->displayError($request, $response, $message);
    }
}
