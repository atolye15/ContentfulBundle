<?php

/**
 * This file is part of the contentful/contentful-bundle package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Atolye15\ContentfulBundle\Controller\Delivery;

use Atolye15\ContentfulBundle\DataCollector\Delivery\ClientDataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;

class ProfilerController
{
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * ProfilerController constructor.
     *
     * @param Profiler    $profiler
     * @param Environment $twig
     */
    public function __construct(Profiler $profiler, Environment $twig)
    {
        $this->profiler = $profiler;
        $this->twig = $twig;
    }

    /**
     * @param string $token
     * @param int    $requestIndex
     *
     * @return Response
     */
    public function detailsAction(string $token, int $requestIndex): Response
    {
        $this->profiler->disable();

        $profile = $this->profiler->loadProfile($token);
        /** @var ClientDataCollector $collector */
        $collector = $profile->getCollector('contentful');
        $messages = $collector->getMessages();

        $message = $messages[$requestIndex];

        $body = $this->twig->render('@Contentful/Collector/details.html.twig', [
            'requestIndex' => $requestIndex,
            'message' => $message,
        ]);

        return new Response($body);
    }
}
