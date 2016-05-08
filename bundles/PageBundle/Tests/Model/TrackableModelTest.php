<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrackableModelTest extends WebTestCase
{
    /**
     * @testdox Test that content is detected as HTML
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testHtmlIsDetectedInContent()
    {
        $mockFactory = $this->getMockBuilder('Mautic\CoreBundle\Factory\MauticFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs(array($mockFactory))
            ->setMethods(array('getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromHtml'))
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn(array());

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromHtml')
            ->willReturn(
                array(
                    '',
                    array()
                )
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn(array());

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'html'),
            array(),
            'email',
            1
        );
    }

    /**
     * @testdox Test that content is detected as plain text
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromText
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testPlainTextIsDetectedInContent()
    {
        $mockFactory = $this->getMockBuilder('Mautic\CoreBundle\Factory\MauticFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs(array($mockFactory))
            ->setMethods(array('getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromText'))
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn(array());

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromText')
            ->willReturn(
                array(
                    '',
                    array()
                )
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn(array());

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'text'),
            array(),
            'email',
            1
        );
    }

    /**
     * @testdox Test that a standard link with a standard query is parsed correctly
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithStandardQuery()
    {
        $url = 'https://foo-bar.com?foo=bar';
        $model = $this->getModel($url);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            array(),
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link without a query parses correctly
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithoutQuery()
    {
        $url = 'https://foo-bar.com';
        $model = $this->getModel($url);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            array(),
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link with a tokenized query parses correctly
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithTokenizedQuery()
    {
        $url   = 'https://foo-bar.com?foo={leadfield=bar}&bar=foo';
        $model = $this->getModel($url, 'https://foo-bar.com?bar=foo');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            array(
                '{leadfield=bar}' => ''
            ),
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}&foo=\{leadfield=bar\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @testdox Test that a token used in place of a URL is not parsed
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedHostIsIgnored()
    {
        $url   = 'http://{leadfield=foo}.com';
        $model = $this->getModel($url, 'http://{leadfield=foo}.com');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            array(
                '{leadfield=foo}' => ''
            ),
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
    }

    /**
     * @testdox Test that a token used in place of a URL is not parsed
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testTokenAsHostIsConvertedToTrackableToken()
    {
        $url   = 'http://{pagelink=1}';
        $model = $this->getModel($url, 'http://foo-bar.com');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            array(
                '{pagelink=1}' => 'http://foo-bar.com'
            ),
            'email',
            1
        );

        $this->assertNotEmpty($trackables, $content);
    }

    /**
     * @testdox Test that a URLs with same base or correctly replaced
     *
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testUrlsWithSameBaseAreReplacedCorrectly()
    {
        $urls = array(
            'https://foo-bar.com',
            'https://foo-bar.com?foo=bar',
        );

        $model = $this->getModel($urls);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($urls, 'html'),
            array(),
            'email',
            1
        );

        foreach ($trackables as $redirectId => $trackable) {
            // If the shared base was correctly parsed, all generated tokens will be in the content
            $this->assertNotFalse(strpos($content, $redirectId), $content);
        }
    }

    /**
     * @param      $urls
     * @param null $tokenUrls
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getModel($urls, $tokenUrls = null)
    {
        if (!is_array($urls)) {
            $urls = array($urls);
        }
        if (null === $tokenUrls) {
            $tokenUrls = $urls;
        } elseif (!is_array($tokenUrls)) {
            $tokenUrls = array($tokenUrls);
        }

        $mockFactory = $this->getMockBuilder('Mautic\CoreBundle\Factory\MauticFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs(array($mockFactory))
            ->setMethods(array('getDoNotTrackList', 'getEntitiesFromUrls'))
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn(array());

        $entities = array();
        foreach ($urls as $k => $url) {
            $entities[$url] = $this->getTrackableEntity($tokenUrls[$k]);
        }

        $mockModel->expects($this->any())
            ->method('getEntitiesFromUrls')
            ->willReturn(
                $entities
            );

        return $mockModel;
    }

    /**
     * @param $url
     *
     * @return Trackable
     */
    protected function getTrackableEntity($url)
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $trackable = new Trackable();
        $trackable->setChannel('email')
            ->setChannelId(1)
            ->setRedirect($redirect)
            ->setHits(rand(1, 10))
            ->setUniqueHits(rand(1, 10));

        return $trackable;
    }

    /**
     * @param      $urls
     * @param      $type
     * @param bool $doNotTrack
     *
     * @return string
     */
    protected function generateContent($urls, $type, $doNotTrack = false)
    {
        $content = '';
        if (!is_array($urls)) {
            $urls = array($urls);
        }

        foreach ($urls as $url) {
            if ($type == 'html') {
                $dnc = ($doNotTrack) ? " mautic:disable-tracking" : "";

                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 <a href="$url"$dnc>$url</a> 321ABC
CONTENT;
            } else {
                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 $url 321ABC
CONTENT;
            }
        }

        return $content;
    }
}
