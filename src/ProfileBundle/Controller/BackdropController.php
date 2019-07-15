<?php
namespace ProfileBundle\Controller;

use AppBundle\Exception\BadRestRequestHttpException;
use AppBundle\Http\ErrorJsonResponse;
use ImageBundle\Image\Image;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AvatarBundle\Parameter\UploadedImageParameter;
use ProfileBundle\Form\BackdropUploadType;
use ProfileBundle\Response\SuccessProfileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BackdropController extends Controller
{

    /**
     * @ApiDoc(
     *  section="Profile",
     *  description= "Загрузить бэкдром к профилю",
     *  authentication=true,
     *  input = {"class" = "ProfileBundle\Form\BackdropUploadType", "name"  = ""},
     *  output = {"class" = "ProfileBundle\Response\SuccessProfileResponse"},
     *  statusCodes = {
     *      200 = "Успешно установлен бэкдроп",
     *      400 = "Некорректное изображение либо аргументы",
     *      404 = "Профиль не найден"
     *  }
     * )
     *
     * @param int $id
     * @param Request $request
     */
    public function uploadAction(int $id, Request $request)
    {
        try {
            $profileService = $this->get('profile.service');

            $profile = $profileService->getById($id);

            $body = $this->get('app.validate_request')->getData($request, BackdropUploadType::class);

            $params = new UploadedImageParameter($body['image']);
            $params->setStartY($body['y']);

            $profileService->uploadBackdrop($profile, $params);
        } catch(NotFoundHttpException $e){
            return new ErrorJsonResponse($e->getMessage(), [], $e->getStatusCode());
        } catch(BadRestRequestHttpException $e){
            return new ErrorJsonResponse($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        } catch(\Exception $e){
            return new ErrorJsonResponse($e->getMessage());
        }

        return new SuccessProfileResponse($profile);
    }

    /**
     * @ApiDoc(
     *  section="Profile",
     *  description= "удалить бэкдроп",
     *  authentication=true,
     *  output = {"class" = "ProfileBundle\Response\SuccessProfileResponse"},
     *  statusCodes = {
     *      200 = "Успешно удалён бэкдроп",
     *      400 = "Некорректное изображение либо аргументы",
     *      404 = "Профиль не найден"
     *  }
     * )
     *
     * @param int $id
     * @param Request $request
     */
    public function deleteAction(int $id)
    {
        try{
            $profileService = $this->get('profile.service');

            $profile = $profileService->getById($id);

            $profileService->deleteBackdrop($profile);

        }catch(NotFoundHttpException $e){
            return new ErrorJsonResponse($e->getMessage(), [], $e->getStatusCode());
        }catch(\Exception $e){
            return new ErrorJsonResponse($e->getMessage());
        }

        return new SuccessProfileResponse($profile);
    }

    /**
     * @ApiDoc(
     *  section="Profile",
     *  description= "Получить бэкдропы для профиля",
     *  authentication=true,
     *  statusCodes = {
     *      200 = "Успешное получение профиля",
     *      404 = "Профиль не найден"
     *  }
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getBackdropPresetsAction()
    {
        $presets = array_map(function(Image $image){
            return $image->jsonSerialize();
        }, $this->get('profile.backdrop.service')->getProfileBackdropPresets());

        return new JsonResponse($presets);
    }

    /**
     * @ApiDoc(
     *  section="Profile",
     *  description= "Загрузить пресет Бэкдроп к профилю",
     *  authentication=true,
     *  output = {"class" = "ProfileBundle\Response\SuccessProfileResponse"},
     * )
     *
     * @param int $id
     * @param Request $request
     */
    public function setBackdropAction(int $id, int $presetId)
    {
        try{
            $profileService = $this->get('profile.service');

            $profile = $profileService->getById($id);

            $image = $this->get('profile.backdrop.service')->getProfileBackdropPreset($presetId);

            $this->get('profile.backdrop.service')->setBackdrop($profile, $image);

            $profileService->save($profile);

        }catch(NotFoundHttpException $e){
            return new ErrorJsonResponse($e->getMessage());
        } catch(\Exception $e){
            return new ErrorJsonResponse($e->getMessage());
        }

        return new SuccessProfileResponse($profile);
    }
}