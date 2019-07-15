<?php
namespace CommentBundle\Response;

use AttachmentBundle\Response\SuccessAttachmentsResponse;
use CommentBundle\Entity\Comment;
use ProfileBundle\Response\SuccessProfileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SuccessCommentResponse extends JsonResponse implements \JsonSerializable
{
    private $entity;

    function __construct(Comment $entity = null, bool $isCreated = false)
    {
        $this->entity = $entity;
        parent::__construct(self::jsonSerialize(), $isCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }


    function jsonSerialize()
    {
        $comment = $this->entity ?? $this->createMockEntity();



        return [
            'id' => $comment->getId(),
            'post_id' => $comment->getPost()->getId(),
            'parent_comment_id' => $comment->getParentComment() ? $comment->getParentComment()->getId() : null,
//            'parent_comment' => $comment->getParentComment() !== null ? (new self($comment->getParentComment()))->jsonSerialize() : null,

            'level' => $comment->getLevel(),
            'created' => $comment->getCreated()->format(\DateTime::W3C),
            'updated' => $comment->getUpdated()->format(\DateTime::W3C),
            'attachments' => (new SuccessAttachmentsResponse($comment->getAttachments()))->jsonSerialize(),
            'profile' => (new SuccessProfileResponse($comment->getProfile()))->jsonSerialize()['entity'],
            'votes' => [
                'state' =>  $comment->getVote() ? $comment->getVote()->getType()->getStringCode() :'none',
                'rating' => $comment->getVotesRating(),
                'positive' => $comment->getVotesPositive(),
                'negative' => $comment->getVotesNegative()
            ],
            'comments_total' => $comment->getCommentsTotal(),
            'comments' => (new SuccessCommentsResponse($comment->getComments()))->jsonSerialize(),
        ];
    }


    public function createMockEntity(): Comment
    {

        $entity = new Comment();
        $entity
            ->setParentCommentId(122)
        ;

        return $entity;
    }
}