<?php
namespace PostBundle\Entity;

use AppBundle\Delete\DeleteAble;
use AppBundle\Delete\DeleteTrait;
use AppBundle\Entity\ModifyDateEntityInterface;
use AppBundle\Entity\ModifyDateEntityTrait;
use AttachmentBundle\Entity\AttachmentableEntity;
use AttachmentBundle\Entity\AttachmentableEntityTrait;
use CommentBundle\Comment\CommentAbleEntity;
use CommentBundle\Comment\CommentAbleEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use ProfileBundle\Entity\Profile;
use TagBundle\Entity\AbstractTaggable;
use VoteBundle\Rating\RatingableEntity;
use VoteBundle\Rating\RatingableEntityTrait;
use VoteBundle\Vote\VoteableEntity;
use VoteBundle\Vote\VoteableEntityTrait;

class Post extends AbstractTaggable
            implements
                ModifyDateEntityInterface,
                AttachmentableEntity,
                VoteableEntity,
                RatingableEntity,
                CommentAbleEntity,
                DeleteAble
{
    use
        ModifyDateEntityTrait,
        AttachmentableEntityTrait,
        VoteableEntityTrait,
        RatingableEntityTrait,
        CommentAbleEntityTrait,
        DeleteTrait
        ;

    private $id;
    private $title;
    private $attachments;
    private $profile;

    public function __construct()
    {
        parent::__construct();
        $this->attachments = new ArrayCollection();
        $this->created = new \DateTime();
        $this->markUpdated();
    }

    private $seo;


    public function getSeo()
    {
        return $this->seo;
    }


    public function setSeo($seo)
    {
        $this->seo = $seo;
        return $this;
    }


    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id):self
    {
        $this->id = $id;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile)
    {
        $this->profile = $profile;
    }
}
