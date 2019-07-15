<?php

namespace PostBundle\Repository;

use AttachmentBundle\Entity\Attachment;
use Doctrine\ORM\NoResultException;
use FeedBundle\Criteria\FeedCriteria;
use FeedBundle\Strategy\HotFeedStrategy;
use FeedBundle\Strategy\VotedFeedStrategy;
use PostBundle\Entity\Post;
use PostBundle\Repository\Command\AddOrder;
use PostBundle\Repository\Command\AddTags;
use ProfileBundle\Entity\Profile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TagBundle\Entity\Tag;

class PostRepository extends \Doctrine\ORM\EntityRepository
{

    public function save(Post $post)
    {
        $em = $this->getEntityManager();
        $em->flush($post);
    }


    public function saveWithTagsAndAttachments(Post $post)
    {
        $em = $this->getEntityManager();
        $tagRep = $em->getRepository(Tag::class);

        $em->persist($post);

        // сохраняем теги
        $tagRep->saveTags($post);

        // сохраняем аттачменты
        $attachmentRep = $em->getRepository(Attachment::class);
        $attachmentRep->saveAttachments($post);

        $em->flush();
    }

    public function getPostById(int $postId): Post
    {
        try {
            $qb = $this->createQueryBuilder('p')
                ->select('p')
                ->where('p.id = :id')
                ->setParameter('id', $postId)
                ->getQuery();

            $result = $qb->getSingleResult();
        } catch(NoResultException $e){
            throw new NotFoundHttpException(sprintf("post wid id= %s not found", $postId));
        }

        return $result;
    }

    public function getPostsByIds(array $ids)
    {

        $posts = $this->findBy(['id' => $ids]);


        if(count($posts) < 1)
            throw new NotFoundHttpException(sprintf("posts with ids= %s not found", implode(',',$ids)));

        return $posts;
    }


    private function getPostsByCriteria(FeedCriteria $criteria)
    {
        try {
            $qb = $this->createQueryBuilder('p')
                ->select('p')
            ;

            AddOrder::addOrder($qb, $criteria);
            AddTags::addOrder($qb, $criteria);

            $qb->andWhere('p.isDeleted = 0');

            if($startDate = $criteria->getStartDate()){
                $qb->andWhere('p.created > :start')
                    ->setParameter('start', $startDate)
                ;
            }

            if($endDate = $criteria->getEndDate()){
                $qb->andWhere('p.created < :end')
                    ->setParameter('end', $endDate)
                ;
            }

            if($profileId = $criteria->getProfileId()){
                $qb->andWhere('p.profile = :profile')
                    ->setParameter('profile', $profileId)
                ;
            }


            $qb->setMaxResults($criteria->getLimit());

            $q = $qb->getQuery();
        } catch(NoResultException $e){
            throw new NotFoundHttpException(sprintf("no posts founded"));
        }

        return $q->getResult();
    }

    public function getPostWithTagsAndAttachmentsByPostId(int $postId)
    {
        try {
            $qb = $this->createQueryBuilder('p')
                ->select('p', 'tags', 'attachments', 'profile')
                ->leftJoin('p.tags', 'tags')
                ->leftJoin('p.attachments', 'attachments')
                ->orderBy('attachments.position', 'ASC')
                ->leftJoin('p.profile', 'profile')
                ->where('p.id = :id')
                ->setParameter('id', $postId)
            ->getQuery();

            $result = $qb->getSingleResult();
        } catch(NoResultException $e){
            throw new NotFoundHttpException(sprintf("post wid id= %s not found", $postId));
        }

        return $result;
    }

    public function getPostsWithTagsAndAttachments(FeedCriteria $criteria)
    {
        // todo узкое место для рефакторинга
        $posts = [];

        if($voteType = $criteria->getVoteType()){

            $strategy = new VotedFeedStrategy($criteria, $this);
            $posts = $strategy->getPosts();
        } else {
            switch($criteria->getOrder()){
                case 'hot':
                    $strategy = new HotFeedStrategy($criteria, $this);
                    $posts =  $strategy->getPosts();
                    break;
                default:
                    $posts = $this->getPostsByCriteria($criteria);
            }
        }

        return $this->getAttachmentsAndTagsByPosts($posts);
    }

    public function getAttachmentsAndTagsByPosts(array $posts): array
    {
        $postIds = array_map(function(Post $post){
            return $post->getId();
        }, $posts);

        $qb = $this->createQueryBuilder('p')
            ->select('p', 'tags', 'attachments')
            ->leftJoin('p.tags', 'tags')
            ->leftJoin('p.attachments', 'attachments')
            ->where('p.id IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->addOrderBy('attachments.position', 'ASC');


        $query =  $qb->getQuery();

        $postsWithAttachments = $query->getResult();

        array_merge_recursive( $posts, $postsWithAttachments);


        return $posts;
    }

    public function searchAdditions($query){
        return  $this->createQueryBuilder('p')
            ->select('p.id, p.title')
            ->where('p.title LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.votesRating','DESC')
            ->setMaxResults(5)
            ->getQuery()->getArrayResult()
            ;
    }


    public function searchFull($query, $cursor = null){
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.title LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.id','DESC')
            ->setMaxResults(20);

            if($cursor) {
                $qb->andWhere('p.id < :cursor')
                ->setParameter('cursor', $cursor)
                ;

            }

            $posts = $qb->getQuery()->getResult();

        return $this->getAttachmentsAndTagsByPosts($posts);
    }

    public function delete(Post $post)
    {
        $em = $this->getEntityManager();

        // тут удаляем комментарии и аттачменты
        if($post->getIsDeleted() == 0){
            $post->markAsDeleted();

            $em->flush($post);
        }
    }

    public function getEntityManager()
    {
        return parent::getEntityManager();
    }


    public function getProfileTotalPosts(Profile $profile)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.profile = :profile')
            ->setParameter('profile', $profile)
        ;

        $posts = $qb->getQuery()->getSingleScalarResult();

        $profile->setPostsTotal($posts);
    }


    public function getTopTagsWithCount($postId, $limit)
    {

        $postRep = $this->getEntityManager()
            ->getRepository(Post::class);


        $topTags = $postRep->createQueryBuilder('p')
            ->select('tags.name, tags.id, count(tags) as total')
            ->join('p.tags', 'tags')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->groupBy('tags.id ')
            ->orderBy('total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;

        $ids = array_map(function($tag){
            return $tag['id'];
        }, $topTags);


        return $postRep->createQueryBuilder('p')
            ->select('p')
            ->join('p.tags', 'tags')
            ->setMaxResults($limit)
            ->where('tags.id IN (:tagsIds)')
            ->andWhere('p.id != :pid')
            ->setParameter('pid', $postId)
            ->setParameter('tagsIds', $ids)
            ->getQuery()
            ->getResult()
        ;


    }

}
