<?php
/**
 * Created by PhpStorm.
 * User: kpurrmann
 * Date: 02.11.15
 * Time: 13:27
 */

namespace Mittwald\Tests\Functional;


use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Response;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestReturnsPdfFileTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = array(
        'fluid'
    );
    /**
     * @var array
     */
    protected $testExtensionsToLoad = array(
        'typo3conf/ext/web2pdf'
    );

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/tt_content.xml');
        $this->setUpFrontendRootPage(1, array(
            'typo3conf/ext/web2pdf/Configuration/TypoScript/constants.txt',
            'typo3conf/ext/web2pdf/Configuration/TypoScript/setup.txt',
            'typo3conf/ext/web2pdf/Tests/Functional/Fixtures/basic.ts',
        ));
    }

    /**
     *
     */
    public function tearDown()
    {
        exec('rm -rf ' . ORIGINAL_ROOT . 'typo3temp/*');

    }

    /**
     * @test
     */
    public function testWeb2PdfRequest()
    {
        $requestParameter = array(
            'id' => 1,
            'tx_web2pdf_pi1' => array(
                'printPage' => 1,
                'argument' => 'printPage',
                'action' => '',
                'controller' => 'Pdf'
            )
        );

        $response = $this->fetchFrontendResponse($requestParameter, true, true);
        $this->assertContains('PDF', $response['stdout']);
    }


    /**
     * @param array $requestArguments
     * @param bool|TRUE $failOnFailure
     * @return array|Response
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    protected function fetchFrontendResponse(array $requestArguments, $failOnFailure = TRUE, $getFullResponse = FALSE)
    {
        if (!empty($requestArguments['url'])) {
            $requestUrl = '/' . ltrim($requestArguments['url'], '/');
        } else {
            $requestUrl = '/?' . GeneralUtility::implodeArrayForUrl('', $requestArguments);
        }
        $arguments = array(
            'documentRoot' => ORIGINAL_ROOT . 'typo3temp/functional-' . substr(sha1(get_class($this)), 0, 7),
            'requestUrl' => 'http://localhost' . $requestUrl,
        );

        $template = new \Text_Template(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/request.tpl');
        $template->setVar(
            array(
                'arguments' => var_export($arguments, TRUE),
                'originalRoot' => ORIGINAL_ROOT,
            )
        );
        $php = \PHPUnit_Util_PHP::factory();
        $response = $php->runJob($template->render());

        if ($getFullResponse) {
            return $response;
        }
        $result = json_decode($response['stdout'], TRUE);


        if ($result === NULL) {
            $this->fail('Frontend Response is empty');
        }

        if ($failOnFailure && $result['status'] === Response::STATUS_Failure) {
            $this->fail('Frontend Response has failure:' . LF . $result['error']);
        }

        $response = new Response($result['status'], $result['content'], $result['error']);
        return $response;
    }
}
