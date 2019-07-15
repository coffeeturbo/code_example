<?php
namespace ProfileBundle\Tests\Path;

use PHPUnit\Framework\Assert;
use ProfileBundle\DataFixtures\ORM\LoadProfileData;
use ProfileBundle\Entity\Profile;
use ProfileBundle\Tests\ProfileController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ProfileBackdropUploadAction extends ProfileController
{

    protected $filePath1600x400 = __DIR__ . '/../../Resources/backdrop/1600x400.jpg';
    protected $filePath1400x200 = __DIR__ . '/../../Resources/backdrop/1400x200.jpg';
    protected $filePath1500x200 = __DIR__ . '/../../Resources/backdrop/1500x200.jpg';
    protected $filePath1500x150 = __DIR__ . '/../../Resources/backdrop/1500x150.jpg';


    public function setUp()
    {
        parent::setUp();

        $this->fixtures['profile'] = $fixtureProfile = new LoadProfileData();
        $fixtureProfile->setContainer($this->container);
        $fixtureProfile->load($this->em);
    }

    public function test200()
    {
        $this->getAuthClient();
        $profile = $this->getSuccessProfile();

        $params = [
            'y' => 0,
        ];

        $file = new UploadedFile($this->filePath1500x200, 'grid-example');

        $this->getBackdropUploadRequestClient($profile->getId(), $params, $file);

        $response = $this->client->getResponse();

        $body = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        Assert::assertFileExists($body['entity']['backdrop']['storage_path']);
    }

    public function testBadMinWidth400()
    {
        $this->getAuthClient();
        $profile = $this->getSuccessProfile();

        $params = [
            'y' => 200,
        ];

        $file = new UploadedFile($this->filePath1400x200, 'grid-example');

        $this->getBackdropUploadRequestClient($profile->getId(), $params, $file);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testBadMinHeight400()
    {
        $this->getAuthClient();
        $profile = $this->getSuccessProfile();

        $params = [
            'y' => 200,
        ];

        $file = new UploadedFile($this->filePath1500x150, 'grid-example');

        $this->getBackdropUploadRequestClient($profile->getId(), $params, $file);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testBadYCoord400()
    {
        $this->getAuthClient();
        $profile = $this->getSuccessProfile();

        $params = [
            'y' => 100,
        ];

        $file = new UploadedFile($this->filePath1500x200, 'grid-example');

        $this->getBackdropUploadRequestClient($profile->getId(), $params, $file);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test404()
    {
        $this->getAuthClient();

        $params = [
            'y' => 200,
        ];

        $file = new UploadedFile($this->filePath1600x400, 'grid-example');

        $this->getBackdropUploadRequestClient(99999, $params, $file);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

}