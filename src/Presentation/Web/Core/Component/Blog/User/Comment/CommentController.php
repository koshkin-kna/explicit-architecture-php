<?php

declare(strict_types=1);

/*
 * This file is part of the Explicit Architecture POC,
 * which is created on top of the Symfony Demo application.
 *
 * (c) Herberto Graça <herberto.graca@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Acme\App\Presentation\Web\Core\Component\Blog\User\Comment;

use Acme\App\Core\Component\Blog\Application\Repository\PostRepositoryInterface;
use Acme\App\Core\Component\Blog\Application\Service\CommentService;
use Acme\App\Core\Component\Blog\Domain\Post\Comment\Comment;
use Acme\App\Core\Component\Blog\Domain\Post\PostId;
use Acme\App\Core\Port\Auth\AuthenticationServiceInterface;
use Acme\App\Core\Port\Auth\AuthorizationServiceInterface;
use Acme\App\Core\Port\TemplateEngine\TemplateEngineInterface;
use Acme\App\Presentation\Web\Core\Port\Form\FormFactoryInterface;
use Acme\App\Presentation\Web\Core\Port\Response\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Herberto Graca <herberto.graca@gmail.com>
 */
class CommentController
{
    /**
     * @var CommentService
     */
    private $commentService;

    /**
     * @var TemplateEngineInterface
     */
    private $templateEngine;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

    /**
     * @var \Acme\App\Core\Port\Auth\AuthorizationServiceInterface
     */
    private $authorizationService;

    /**
     * @var AuthenticationServiceInterface
     */
    private $authenticationService;

    public function __construct(
        CommentService $commentService,
        TemplateEngineInterface $templateEngine,
        ResponseFactoryInterface $responseFactory,
        FormFactoryInterface $formFactory,
        PostRepositoryInterface $postRepository,
        AuthorizationServiceInterface $authorizationService,
        AuthenticationServiceInterface $authenticationService
    ) {
        $this->commentService = $commentService;
        $this->templateEngine = $templateEngine;
        $this->responseFactory = $responseFactory;
        $this->formFactory = $formFactory;
        $this->postRepository = $postRepository;
        $this->authorizationService = $authorizationService;
        $this->authenticationService = $authenticationService;
    }

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        $this->authorizationService->denyAccessUnlessGranted([AuthorizationServiceInterface::ROLE_AUTHENTICATED]);

        $post = $this->postRepository->findBySlug($request->getAttribute('postSlug'));
        $comment = new Comment();

        $form = $this->formFactory->createCommentForm($comment);
        $form->handleRequest($request);

        if (!$form->shouldBeProcessed()) {
            return $this->templateEngine->renderResponse(
                '@Blog/User/Comment/edit_error.html.twig',
                EditViewModel::fromPostAndForm($post, $form)
            );
        }

        $this->commentService->create($post, $comment, $this->authenticationService->getLoggedInUserId());

        return $this->responseFactory->redirectToRoute('post', ['slug' => $post->getSlug()]);
    }

    /**
     * This controller is called directly via the render() function in the
     * Blog/Anonymous/Post/get.html.twig template. That's why it's not needed to define
     * a route name for it.
     */
    public function edit(PostId $postId): ResponseInterface
    {
        return $this->templateEngine->renderResponse(
            '@Blog/User/Comment/edit.html.twig',
            EditViewModel::fromPostAndForm(
                $this->postRepository->find($postId),
                $this->formFactory->createCommentForm()
            )
        );
    }
}
