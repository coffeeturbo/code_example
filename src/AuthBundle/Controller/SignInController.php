<?php

namespace AuthBundle\Controller;

use AppBundle\Exception\BadRestRequestHttpException;
use AppBundle\Http\ErrorJsonResponse;
use AuthBundle\Form\SignInType;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class SignInController extends Controller
{
    /**
     * Авторизация аккаунта
     *
     * @ApiDoc(
     *  section = "Auth",
     *  input = {"class" = "AuthBundle\Form\SignInType", "name"  = ""},
     *  output = {"class" = "AuthBundle\Response\SuccessAuthResponse"},
     *  statusCodes = {
     *      200 = "Успешная авторизация",
     *      400 = "Неправильный запрос",
     *      401 = {
     *          "Неверный логин или пароль",
     *          "Пользователь не найден"
     *      }
     *  },
     *  headers = {
     *      {
     *          "name" = "Accept",
     *          "default" = "application/json",
     *          "description" = "Если не указан будет сгенерировано всплывающее окно"
     *      }
     *  }
     * )
     * @param Request $request
     * @return Response
     */
    public function signInAction(Request $request)
    {
        try {
            $body = $this->get('app.validate_request')->getData($request, SignInType::class);
            $account = $this->get('auth.service')->validateCredentials($body["username"], $body["password"]);
        } catch (BadRestRequestHttpException $e) {
            return new ErrorJsonResponse($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        } catch (UnauthorizedHttpException $e) {
            return new ErrorJsonResponse($e->getMessage(), [], $e->getStatusCode());
        }

        $token = $this->get('lexik_jwt_authentication.jwt_manager')->create($account);

        $event = new AuthenticationSuccessEvent(['token' => $token], $account, new Response());

        if ($body["dont_remember"] !== true) {
            $this->get('gesdinet.jwtrefreshtoken.send_token')->attachRefreshToken($event);
        }

        return $this->forward('AuthBundle:RenderToken:render', $event->getData());
    }
}