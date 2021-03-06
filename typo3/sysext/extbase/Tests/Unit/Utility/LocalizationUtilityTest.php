<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Prophecy\Argument;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LocalizationUtilityTest extends UnitTestCase
{
    /**
     * Instance of configurationManagerInterface, injected to subject
     *
     * @var ConfigurationManagerInterface
     */
    protected $configurationManagerInterfaceProphecy;

    /**
     * LOCAL_LANG array fixture
     *
     * @var array
     */
    protected $LOCAL_LANG = [];

    /**
     * File path of locallang for extension "core"
     * @var string
     */
    protected $languageFilePath = '';

    /**
     * Prepare class mocking some dependencies
     */
    protected function setUp()
    {
        $this->languageFilePath = $this->getLanguageFilePath('core');
        $this->LOCAL_LANG = [
            $this->languageFilePath => [
                'default' => [
                    'key1' => [
                        [
                            'source' => 'English label for key1',
                            'target' => 'English label for key1',
                        ]
                    ],
                    'key2' => [
                        [
                            'source' => 'English label for key2',
                            'target' => 'English label for key2',
                        ]
                    ],
                    'key3' => [
                        [
                            'source' => 'English label for key3',
                            'target' => 'English label for key3',
                        ]
                    ],
                    'key4' => [
                        [
                            'source' => 'English label for key4',
                            'target' => 'English label for key4',
                        ]
                    ],
                    'keyWithPlaceholder' => [
                        [
                            'source' => 'English label with number %d',
                            'target' => 'English label with number %d',
                        ]
                    ],
                ],
                'dk' => [
                    'key1' => [
                        [
                            'source' => 'English label for key1',
                            'target' => 'Dansk label for key1',
                        ]
                    ],
                    // not translated in dk => no target (llxml)
                    'key2' => [
                        [
                            'source' => 'English label for key2',
                        ]
                    ],
                    'key3' => [
                        [
                            'source' => 'English label for key3',
                        ]
                    ],
                    // not translated in dk => empty target (xliff)
                    'key4' => [
                        [
                            'source' => 'English label for key4',
                            'target' => '',
                        ]
                    ],
                    // not translated in dk => empty target (xliff)
                    'key5' => [
                        [
                            'source' => 'English label for key5',
                            'target' => '',
                        ]
                    ],
                    'keyWithPlaceholder' => [
                        [
                            'source' => 'English label with number %d',
                        ]
                    ],
                ],
                // fallback language for labels which are not translated in dk
                'dk_alt' => [
                    'key1' => [
                        [
                            'source' => 'English label for key1',
                        ]
                    ],
                    'key2' => [
                        [
                            'source' => 'English label for key2',
                            'target' => 'Dansk alternative label for key2',
                        ]
                    ],
                    'key3' => [
                        [
                            'source' => 'English label for key3',
                        ]
                    ],
                    // not translated in dk_alt => empty target (xliff)
                    'key4' => [
                        [
                            'source' => 'English label for key4',
                            'target' => '',
                        ]
                    ],
                    'key5' => [
                        [
                            'source' => 'English label for key5',
                            'target' => 'Dansk alternative label for key5',
                        ]
                    ],
                    'keyWithPlaceholder' => [
                        [
                            'source' => 'English label with number %d',
                        ]
                    ],
                ],

            ],
        ];

        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $this->configurationManagerInterfaceProphecy = $this->prophesize(ConfigurationManagerInterface::class);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        $property->setValue($this->configurationManagerInterfaceProphecy->reveal());

        $localizationFactoryProphecy = $this->prophesize(LocalizationFactory::class);
        GeneralUtility::setSingletonInstance(LocalizationFactory::class, $localizationFactoryProphecy->reveal());
        $localizationFactoryProphecy->getParsedData(Argument::cetera(), 'foo')->willReturn([]);
    }

    /**
     * Reset static properties
     */
    protected function tearDown()
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        $property->setValue(null);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue([]);

        GeneralUtility::purgeInstances();

        parent::tearDown();
    }

    /**
     * @param string $extensionName
     * @return string
     */
    protected function getLanguageFilePath(string $extensionName): string
    {
        return PATH_typo3 . 'sysext/' . $extensionName . '/Resources/Private/Language/locallang.xlf';
    }

    /**
     * @test
     */
    public function implodeTypoScriptLabelArrayWorks()
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);
        $method = $reflectionClass->getMethod('flattenTypoScriptLabelArray');
        $method->setAccessible(true);

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key3.subkey1' => 'subvalue1',
            'key3.subkey2.subsubkey' => 'val'
        ];
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                '_typoScriptNodeValue' => 'value3',
                'subkey1' => 'subvalue1',
                'subkey2' => [
                    'subsubkey' => 'val'
                ]
            ]
        ];
        $result = $method->invoke(null, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function translateForEmptyStringKeyReturnsNull()
    {
        $this->assertNull(LocalizationUtility::translate('', 'extbase'));
    }

    /**
     * @test
     */
    public function translateForEmptyStringKeyWithArgumentsReturnsNull()
    {
        $this->assertNull(LocalizationUtility::translate('', 'extbase', ['argument']));
    }

    /**
     * @return array
     */
    public function translateDataProvider(): array
    {
        return [
            'get translated key' =>
            ['key1', 'dk', 'Dansk label for key1'],

            'fallback to English when translation is missing for key' =>
            ['key2', 'dk', 'English label for key2'],

            'fallback to English for non existing language' =>
            ['key2', 'xx', 'English label for key2'],

            'replace placeholder with argument' =>
            ['keyWithPlaceholder', 'default', 'English label with number 100', [], [100]],

            'get translated key from primary language' =>
            ['key1', 'dk', 'Dansk label for key1', ['dk_alt']],

            'fallback to alternative language if translation is missing(llxml)' =>
            ['key2', 'dk', 'Dansk alternative label for key2', ['dk_alt']],

            'fallback to alternative language if translation is missing(xlif)' =>
            ['key5', 'dk', 'Dansk alternative label for key5', ['dk_alt']],

            'fallback to English for label not translated in dk and dk_alt(llxml)' =>
            ['key3', 'dk', 'English label for key3', ['dk_alt']],

            'fallback to English for label not translated in dk and dk_alt(xlif)' =>
            ['key4', 'dk', 'English label for key4', ['dk_alt']],
        ];
    }

    /**
     * @param string $key
     * @param string $languageKey
     * @param string $expected
     * @param array $altLanguageKeys
     * @param array $arguments
     * @dataProvider translateDataProvider
     * @test
     */
    public function translateTestWithBackendUserLanguage($key, $languageKey, $expected, array $altLanguageKeys = [], array $arguments = null)
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($this->LOCAL_LANG);

        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();
        $backendUserAuthenticationProphecy->uc = [
            'lang' => $languageKey,
        ];
        $GLOBALS['LANG'] = $this->LOCAL_LANG;

        $this->assertEquals($expected, LocalizationUtility::translate($key, 'core', $arguments, null, $altLanguageKeys));
    }

    /**
     * @param string $key
     * @param string $languageKey
     * @param string $expected
     * @param array $altLanguageKeys
     * @param array $arguments
     * @dataProvider translateDataProvider
     * @test
     */
    public function translateTestWithExplicitLanguageParameters($key, $languageKey, $expected, array $altLanguageKeys = [], array $arguments = null)
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($this->LOCAL_LANG);
        $GLOBALS['LANG'] = $this->LOCAL_LANG;
        $this->assertEquals($expected, LocalizationUtility::translate($key, 'core', $arguments, $languageKey, $altLanguageKeys));
    }

    /**
     * @return array
     */
    public function loadTypoScriptLabelsProvider(): array
    {
        return [
            'override labels with typoscript' => [
                'LOCAL_LANG' => [
                    $this->getLanguageFilePath('core') => [
                        'dk' => [
                            'key1' => [
                                [
                                    'source' => 'English label for key1',
                                    'target' => 'Dansk label for key1 core',
                                ]
                            ],
                            'key2' => [
                                [
                                    'source' => 'English label for key2',
                                ]
                            ],
                            'key3.subkey1' => [
                                [
                                    'source' => 'English label for key3',
                                ]
                            ],
                        ],
                    ],
                    $this->getLanguageFilePath('backend') => [
                        'dk' => [
                            'key1' => [
                                [
                                    'source' => 'English label for key1',
                                    'target' => 'Dansk label for key1 backend',
                                ]
                            ],
                            'key2' => [
                                [
                                    'source' => 'English label for key2',
                                ]
                            ],
                            'key3.subkey1' => [
                                [
                                    'source' => 'English label for key3',
                                ]
                            ],
                        ],
                    ],
                ],
                'typoscript LOCAL_LANG' => [
                    '_LOCAL_LANG' => [
                        'dk' => [
                            'key1' => 'key1 value from TS core',
                            'key3' => [
                                'subkey1' => 'key3.subkey1 value from TS core',
                                // this key doesn't exist in xml files
                                'subkey2' => [
                                    'subsubkey' => 'key3.subkey2.subsubkey value from TS core'
                                ]
                            ]
                        ]
                    ]
                ],
                'language key' => 'dk',
                'expected' => [
                    'key1' => [
                        [
                            'source' => 'English label for key1',
                            'target' => 'key1 value from TS core',
                        ]
                    ],
                    'key2' => [
                        [
                            'source' => 'English label for key2',
                        ]
                    ],
                    'key3.subkey1' => [
                        [
                            'source' => 'English label for key3',
                            'target' => 'key3.subkey1 value from TS core',
                        ]
                    ],
                    'key3.subkey2.subsubkey' => [
                        [
                            'target' => 'key3.subkey2.subsubkey value from TS core',
                        ]
                    ],
                ],
            ]
        ];
    }

    /**
     * Tests whether labels from xml are overwritten by TypoScript labels
     *
     * @param array $LOCAL_LANG
     * @param array $typoScriptLocalLang
     * @param string $languageKey
     * @param array $expected
     * @dataProvider loadTypoScriptLabelsProvider
     * @test
     */
    public function loadTypoScriptLabels(array $LOCAL_LANG, array $typoScriptLocalLang, $languageKey, array $expected)
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($LOCAL_LANG);

        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManagerInterfaceProphecy
            ->getConfiguration($configurationType, 'core', null)
            ->shouldBeCalled()
            ->willReturn($typoScriptLocalLang);

        $method = $reflectionClass->getMethod('loadTypoScriptLabels');
        $method->setAccessible(true);
        $method->invoke(null, 'core', $this->languageFilePath);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $result = $property->getValue();

        $this->assertEquals($expected, $result[$this->languageFilePath][$languageKey]);
    }

    /**
     * @test
     */
    public function clearLabelWithTypoScript()
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($this->LOCAL_LANG);

        $typoScriptLocalLang = [
            '_LOCAL_LANG' => [
                'dk' => [
                    'key1' => '',
                ]
            ]
        ];

        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManagerInterfaceProphecy
            ->getConfiguration($configurationType, 'core', null)
            ->shouldBeCalled()
            ->willReturn($typoScriptLocalLang);

        $method = $reflectionClass->getMethod('loadTypoScriptLabels');
        $method->setAccessible(true);
        $method->invoke(null, 'core', $this->languageFilePath);

        $GLOBALS['LANG'] = $this->LOCAL_LANG;

        $result = LocalizationUtility::translate('key1', 'core', null, 'dk');
        $this->assertNotNull($result);
        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function translateThrowsExceptionWithEmptyExtensionNameIfKeyIsNotPrefixedWithLLL()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1498144052);
        LocalizationUtility::translate('foo/bar', '');
    }
}
