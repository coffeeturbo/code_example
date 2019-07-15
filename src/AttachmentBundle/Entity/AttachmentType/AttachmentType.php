<?php
namespace AttachmentBundle\Entity\AttachmentType;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AttachmentType
{
    abstract function getIntCode();
    abstract function getStringCode();

    static public function createFromIntCode(int $code)
    {
        switch($code){
            case AttachmentTypeText::INT_CODE:
                return new AttachmentTypeText();
            case AttachmentTypeVideoYouTube::INT_CODE:
                return new AttachmentTypeVideoYouTube();
            case AttachmentTypeImage::INT_CODE:
                return new AttachmentTypeImage();

            default:
                throw new NotFoundHttpException(
                    sprintf("Attachment type with int code %s not found", $code));
        }
    }

    static public function createFromStringCode($stringCode): AttachmentType
    {
        switch(strtolower($stringCode)){
            case AttachmentTypeText::STRING_CODE:
                return new AttachmentTypeText();
            case AttachmentTypeVideoYouTube::STRING_CODE:
                return new AttachmentTypeVideoYouTube();
            case AttachmentTypeImage::STRING_CODE:
                return new AttachmentTypeImage();
            default:
                throw new NotFoundHttpException(
                    sprintf("Attachment type with string code %s not found", $stringCode));
        }
    }
}