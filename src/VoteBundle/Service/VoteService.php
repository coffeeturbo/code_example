<?php
namespace VoteBundle\Service;

use CommentBundle\Entity\Comment;
use PostBundle\Entity\Post;
use ProfileBundle\Entity\Profile;
use ProfileBundle\Repository\ProfileRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VoteBundle\Criteria\VoteContentCriteria;
use VoteBundle\Entity\Vote;
use VoteBundle\Entity\VoteContentType\VoteContentType;
use VoteBundle\Entity\VoteContentType\VoteContentTypeComment;
use VoteBundle\Entity\VoteContentType\VoteContentTypePost;
use VoteBundle\Entity\VoteType\VoteTypeNegative;
use VoteBundle\Entity\VoteType\VoteTypePositive;
use VoteBundle\Event\VoteEvent;
use VoteBundle\Event\VoteEvents;
use VoteBundle\Formatter\VoteFormatter;
use VoteBundle\Repository\VoteRepository;
use VoteBundle\Vote\VoteableEntity;
use VoteBundle\Vote\VoteEntity;

class VoteService
{
    private $voteRepository;
    private $eventDispatcher;
    private $postVoteWeight;
    private $commentVoteWeight;
    private $profileRepository;

    public function __construct(
        VoteRepository $voteRepository,
        int $postVoteWeight,
        int $commentVoteWeight,
        EventDispatcherInterface $eventDispatcher,
        ProfileRepository $profileRepository
    ){
        $this->voteRepository = $voteRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->postVoteWeight = $postVoteWeight;
        $this->commentVoteWeight = $commentVoteWeight;
        $this->profileRepository = $profileRepository;
    }

    public function getVoteRepository(): VoteRepository
    {
        return $this->voteRepository;
    }



    public function findVote(Vote $vote): ?Vote
    {
       $existsVote = $this->voteRepository->findOneBy([
           'profile' => $vote->getProfile(),
           'contentId' => $vote->getVoteableEntity()->getId(),
           'contentType' => $vote->getVoteableEntity()->getType()->getIntCode(),
       ]);

       return ($existsVote instanceof Vote) ? $existsVote : null;
    }

    public function create(Vote $vote)
    {

        $this->voteRepository->save($vote);

        $this->eventDispatcher->dispatch(
            VoteEvents::VOTE_CREATED,
            new VoteEvent($vote)

        );

    }

    public function delete(Vote $vote)
    {
        // todo сделать проверку на хозяина поста или модератора

        $this->voteRepository->remove($vote);

        $this->eventDispatcher->dispatch(
            VoteEvents::VOTE_DELETED,
            new VoteEvent($vote)

        );
    }

    public function attachVote(VoteableEntity $entity, VoteEntity $vote)
    {
        $voteWeight = 1;

        switch($vote->getType()->getIntCode()){
            case VoteTypePositive::INT_CODE:
                $entity->increaseVotesRating($voteWeight);
                $entity->increaseVotesPositive();
            break;

            case VoteTypeNegative::INT_CODE:
                $entity->decreaseVotesRating($voteWeight);
                $entity->increaseVotesNegative();
            break;
        }
    }

    public function detach(VoteableEntity $entity, Vote $vote)
    {
        $voteWeight = 1;

        switch($vote->getType()->getIntCode()){
            case VoteTypePositive::INT_CODE:
                $entity->decreaseVotesRating($voteWeight);
                $entity->decreaseVotesPositive();
                break;

            case VoteTypeNegative::INT_CODE:
                $entity->increaseVotesRating($voteWeight);
                $entity->decreaseVotesNegative();
                break;
        }
    }

    public function attachVoteToProfile(Vote $vote, Profile $profile)
    {
        $voteWeight = $this->countVoteWeight($vote->getVoteableEntity()->getType());

        switch($vote->getType()->getIntCode()){
            case VoteTypePositive::INT_CODE:
                $profile->increaseVotesRating($voteWeight);
                break;
            case VoteTypeNegative::INT_CODE:
                $profile->decreaseVotesRating($voteWeight);
                break;
            default:
                throw new \Exception(
                    sprintf("unknown vote type %s", $vote->getType()->getStringCode())
                );

        }

        $this->profileRepository->save($profile);
    }

    public function detachVoteFromProfile(Vote $vote, Profile $profile)
    {
        $voteWeight = $this->countVoteWeight($vote->getVoteableEntity()->getType());
        switch($vote->getType()->getIntCode()){
            case VoteTypePositive::INT_CODE:
                $profile->decreaseVotesRating($voteWeight);
                break;
            case VoteTypeNegative::INT_CODE:
                $profile->increaseVotesRating($voteWeight);
                break;
            default:
                throw new \Exception(
                    sprintf("unknown vote type %s", $vote->getType()->getStringCode())
                );

        }

        $this->profileRepository->save($profile);
    }

    private function countVoteWeight(VoteContentType $type): int
    {

        switch($type->getIntCode()){
            case VoteContentTypePost::INT_CODE:
                return 1 * $this->postVoteWeight;
            case VoteContentTypeComment::INT_CODE:
                return 1 * $this->commentVoteWeight;

            default: return 1;
        }

    }

    public function getVotesToPosts(array $posts, $profile)
    {
        $postIds = array_map(function(Post $post){
            return $post->getId();
        }, $posts);

        $votes = $this->voteRepository->getVotesByPostIds($postIds, $profile);

        $this->attachVotesToPosts($posts, $votes);
    }

    public function attachVotesToPosts($posts, $votes)
    {
        array_walk($posts, function(Post $post) use ($votes){
            /** @var Vote $vote */
            foreach($votes as $vote) {
                if($post->getId() === $vote->getContentId()){
                    $post->setVote($vote);
                    break;
                }
            }
        });
    }

    public function getVoteToPost(Post $post, Profile $profile)
    {
        $vote = $this->voteRepository->getVoteByPost($post, $profile);

        if($vote) $post->setVote($vote);
    }

    public function getVotesToComments(array &$comments, $profile)
    {

        $object = null;
        foreach($comments as $comment){
            $object = $comment;
        }

        if(is_array($object)){
            $this->arrayCommentDecorator($comments, $profile);

        }else {
            $this->objectsCommentDecorator($comments, $profile);
        }


    }


    private function objectsCommentDecorator(array &$comments, $profile)
    {
        $commentIds = array_map(function(Comment $comment){
            return $comment->getId();
        }, $comments);

        $votes = $this->voteRepository->getVotesByCommentIds($commentIds, $profile);

        array_walk($comments, function(Comment $comment) use ($votes){
            /** @var Vote $vote */
            foreach($votes as $vote) {
                if($comment->getId() === $vote->getContentId()){
                    $comment->setVote($vote);
                    break;
                }
            }
        });
    }

    private function arrayCommentDecorator(array &$comments, $profile)
    {
        $commentIds = array_map(function($comment){
            return $comment['id'];
        }, $comments);

        $votes = $this->voteRepository->getVotesByCommentIds($commentIds, $profile);

        array_walk($comments, function(array &$comment) use ($votes){
            /** @var Vote $vote */
            foreach($votes as $vote){
                if($comment['id'] === $vote->getContentId()){


                    // todo написать форматер войта
                    $comment['vote'] = (new VoteFormatter($vote))->format() ;
                    break;
                }
            }
        });
    }


    public function getVotedContent(VoteContentCriteria $contentCriteria)
    {
        switch($contentCriteria->getContentType()->getIntCode()){
            case VoteContentTypeComment::INT_CODE:
                return $this->voteRepository->getVotedContentByCriteria($contentCriteria);
            break;

            case VoteContentTypePost::INT_CODE:
                return $this->voteRepository->getVotedContentByCriteria($contentCriteria);
            break;

            default: throw new \Exception("unknown int content type");
        }
    }
}