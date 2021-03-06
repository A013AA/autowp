<?php

namespace Application;

use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\Factory\InvokableFactory;

use Autowp\ExternalLoginService\Factory as ExternalLoginServiceFactory;
use Autowp\Image;
use Autowp\TextStorage;

use Zend_Application_Resource_Db;
use Zend_Application_Resource_Cachemanager;
use Zend_Application_Resource_Session;
use Zend_Cache_Manager;
use Zend_Db_Adapter_Abstract;
use Zend_View;

return [

    'controllers' => [
        'factories' => [
            Controller\AboutController::class => function($sm) {
                $acl = $sm->get(Acl::class);
                return new Controller\AboutController($acl);
            },
            Controller\AccountController::class => function($sm) {
                $service = $sm->get(Service\UsersService::class);
                $emailForm = $sm->get('AccountEmailForm');
                $profileForm = $sm->get('AccountProfileForm');
                $settingsForm = $sm->get('AccountSettingsForm');
                $photoForm = $sm->get('AccountPhotoForm');
                $changePasswordForm = $sm->get('ChangePasswordForm');
                $deleteUserForm = $sm->get('DeleteUserForm');
                $translator = $sm->get('translator');
                $config = $sm->get('Config');
                $externalLoginFactory = $sm->get(ExternalLoginServiceFactory::class);
                return new Controller\AccountController($service, $translator, $emailForm, $profileForm, $settingsForm, $photoForm, $changePasswordForm, $deleteUserForm, $externalLoginFactory, $config['hosts']);
            },
            Controller\ArticlesController::class     => InvokableFactory::class,
            Controller\BanController::class          => InvokableFactory::class,
            Controller\BrandsController::class => function($sm) {
                $cache = $sm->get('longCache');
                return new Controller\BrandsController($cache);
            },
            Controller\CategoryController::class => function($sm) {
                $cache = $sm->get('longCache');
                return new Controller\CategoryController($cache);
            },
            Controller\ChartController::class        => InvokableFactory::class,
            Controller\CommentsController::class => function($sm) {
                $commentForm = $sm->get('CommentForm');
                return new Controller\CommentsController($commentForm);
            },
            Controller\CutawayController::class      => InvokableFactory::class,
            Controller\DonateController::class       => InvokableFactory::class,
            Controller\FactoriesController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\FactoriesController($textStorage);
            },
            Controller\FeedbackController::class     => function($sm) {
                $form = $sm->get('FeedbackForm');
                $transport = $sm->get('MailTransport');
                $options = $sm->get('Config')['feedback'];
                return new Controller\FeedbackController($form, $transport, $options);
            },
            Controller\ForumsController::class => function($sm) {
                $newTopicForm = $sm->get('ForumsTopicNewForm');
                $commentForm = $sm->get('CommentForm');
                $transport = $sm->get('MailTransport');
                $translator = $sm->get('translator');
                return new Controller\ForumsController($newTopicForm, $commentForm, $transport, $translator);
            },
            Controller\IndexController::class        => InvokableFactory::class,
            Controller\InboxController::class        => InvokableFactory::class,
            Controller\InfoController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\InfoController($textStorage);
            },
            Controller\LogController::class          => InvokableFactory::class,
            Controller\LoginController::class => function($sm) {
                $service = $sm->get(Service\UsersService::class);
                $form = $sm->get('LoginForm');
                $translator = $sm->get('translator');
                $externalLoginFactory = $sm->get(ExternalLoginServiceFactory::class);
                $config = $sm->get('Config');

                return new Controller\LoginController($service, $form, $translator, $externalLoginFactory, $config['hosts']);
            },
            Controller\MapController::class          => InvokableFactory::class,
            Controller\MostsController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\MostsController($textStorage);
            },
            Controller\NewController::class          => InvokableFactory::class,
            Controller\MuseumsController::class      => InvokableFactory::class,
            Controller\PictureController::class      => InvokableFactory::class,
            Controller\PictureFileController::class  => InvokableFactory::class,
            Controller\PulseController::class        => InvokableFactory::class,
            Controller\RegistrationController::class => function($sm) {
                $service = $sm->get(Service\UsersService::class);
                $form = $sm->get('RegistrationForm');
                return new Controller\RegistrationController($service, $form);
            },
            Controller\RestorePasswordController::class => function($sm) {
                $service = $sm->get(Service\UsersService::class);
                $restoreForm = $sm->get('RestorePasswordForm');
                $newPasswordForm = $sm->get('NewPasswordForm');
                $transport = $sm->get('MailTransport');
                return new Controller\RestorePasswordController($service, $restoreForm, $newPasswordForm, $transport);
            },
            Controller\RulesController::class        => InvokableFactory::class,
            Controller\TelegramController::class => function($sm) {
                $service = $sm->get(Service\TelegramService::class);
                return new Controller\TelegramController($service);
            },
            Controller\TwinsController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                $cache = $sm->get('longCache');
                return new Controller\TwinsController($textStorage, $cache);
            },
            Controller\UsersController::class => function($sm) {
                $cache = $sm->get('longCache');
                return new Controller\UsersController($cache);
            },
            Controller\VotingController::class       => InvokableFactory::class,
            Controller\Api\ContactsController::class => InvokableFactory::class,
            Controller\Api\PictureController::class  => InvokableFactory::class,
            Controller\Api\UsersController::class => InvokableFactory::class,
            Controller\Console\BuildController::class => InvokableFactory::class,
            Controller\Console\ImageStorageController::class => InvokableFactory::class,
            Controller\Console\MaintenanceController::class => function($sm) {
                $db = $sm->get(Zend_Db_Adapter_Abstract::class);
                $sessionConfig = $sm->get('Config')['session'];
                return new Controller\Console\MaintenanceController($db, $sessionConfig);
            },
            Controller\Console\MessageController::class => InvokableFactory::class,
            Controller\Console\MidnightController::class => InvokableFactory::class,
            Controller\Console\PicturesController::class => InvokableFactory::class,
            Controller\Console\SpecsController::class => InvokableFactory::class,
            Controller\Console\TelegramController::class => function($sm) {
                $service = $sm->get(Service\TelegramService::class);
                return new Controller\Console\TelegramController($service);
            },
            Controller\Console\TrafficController::class => InvokableFactory::class,
            Controller\Console\TwitterController::class => function($sm) {
                $twitterConfig = $sm->get('Config')['twitter'];
                return new Controller\Console\TwitterController($twitterConfig);
            },
            Controller\Console\UsersController::class => function($sm) {
                $service = $sm->get(Service\UsersService::class);
                return new Controller\Console\UsersController($service);
            },
            Controller\Moder\AttrsController::class => InvokableFactory::class,
            Controller\Moder\BrandsController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                $logoForm = $sm->get('BrandLogoForm');
                $descForm = $sm->get('DescriptionForm');
                return new Controller\Moder\BrandsController($textStorage, $logoForm, $descForm);
            },
            Controller\Moder\CategoryController::class => function($sm) {
                return new Controller\Moder\CategoryController();
            },
            Controller\Moder\CommentsController::class => function($sm) {
                $form = $sm->get('ModerCommentsFilterForm');
                return new Controller\Moder\CommentsController($form);
            },
            Controller\Moder\EnginesController::class => function($sm) {
                $filterForm = $sm->get('ModerFactoryFilterForm');
                $editForm = $sm->get('ModerEngineForm');
                return new Controller\Moder\EnginesController($filterForm, $editForm);
            },
            Controller\Moder\FactoryController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                $addForm = $sm->get('ModerFactoryAddForm');
                $editForm = $sm->get('ModerFactoryEditForm');
                $descForm = $sm->get('DescriptionForm');
                $filterForm = $sm->get('ModerFactoryFilterForm');
                return new Controller\Moder\FactoryController($textStorage, $addForm, $editForm, $descForm, $filterForm);
            },
            Controller\Moder\HotlinkController::class => InvokableFactory::class,
            Controller\Moder\IndexController::class => function($sm) {
                $form = $sm->get('AddBrandForm');
                return new Controller\Moder\IndexController($form);
            },
            Controller\Moder\MuseumController::class => function($sm) {
                $form = $sm->get('MuseumForm');
                return new Controller\Moder\MuseumController($form);
            },
            Controller\Moder\PagesController::class => InvokableFactory::class,
            Controller\Moder\PerspectivesController::class => InvokableFactory::class,
            Controller\Moder\PicturesController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\Moder\PicturesController($textStorage);
            },
            Controller\Moder\RightsController::class => function($sm) {
                $acl = $sm->get(Acl::class);
                $cache = $sm->get('longCache');
                $roleForm = $sm->get('ModerAclRoleForm');
                $ruleForm = $sm->get('ModerAclRuleForm');
                $roleParentForm = $sm->get('ModerAclRoleParentForm');
                return new Controller\Moder\RightsController($acl, $cache, $roleForm, $ruleForm, $roleParentForm);
            },
            Controller\Moder\TrafficController::class => InvokableFactory::class,
            Controller\Moder\TwinsController::class => function($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                $editForm = $sm->get('ModerTwinsEditForm');
                $descForm = $sm->get('DescriptionForm');
                return new Controller\Moder\TwinsController($textStorage, $editForm, $descForm);
            },
            Controller\Moder\UsersController::class => InvokableFactory::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'catalogue'       => Controller\Plugin\Catalogue::class,
            'log'             => Controller\Plugin\Log::class,
            'pictureVote'     => Controller\Plugin\PictureVote::class,
            'forbiddenAction' => Controller\Plugin\ForbiddenAction::class
        ],
        'factories' => [
            'car' => function ($sm) {
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\Plugin\Car($textStorage);
            },
            'imageStorage' => function($sm) {
                $storage = $sm->get(Image\Storage::class);
                return new Controller\Plugin\ImageStorage($storage);
            },
            'oauth2' => Factory\OAuth2PluginFactory::class,
            'user' => function($sm) {
                $acl = $sm->get(Acl::class);
                $config = $sm->get('Config');
                return new Controller\Plugin\User($acl, $config['hosts']);
            },
            'language' => function($sm) {
                $language = $sm->get(Language::class);
                return new Controller\Plugin\Language($language);
            },
            'pic' => function($sm) {
                $viewHelperManager = $sm->get('ViewHelperManager');
                $carHelper = $viewHelperManager->get('car');
                $textStorage = $sm->get(TextStorage\Service::class);
                return new Controller\Plugin\Pic($textStorage, $carHelper);
            },
            'fileSize' => function($sm) {
                return new Controller\Plugin\FileSize(
                    $sm->get(Language::class),
                    $sm->get(FileSize::class)
                );
            },
        ]
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'forbidden_template'       => 'error/403',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/403'               => __DIR__ . '/../view/error/403.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'car'         => View\Helper\Car::class,
            'pageEnv'     => View\Helper\PageEnv::class,
            'page'        => View\Helper\Page::class,
            'htmlA'       => View\Helper\HtmlA::class,
            'htmlImg'     => View\Helper\HtmlImg::class,
            'sidebar'     => View\Helper\Sidebar::class,
            'pageTitle'   => View\Helper\PageTitle::class,
            'breadcrumbs' => View\Helper\Breadcrumbs::class,
            'humanTime'   => View\Helper\HumanTime::class,
            'markdown'    => View\Helper\Markdown::class,
            'pastTimeIndicator' => View\Helper\PastTimeIndicator::class,
            'inlinePicture' => View\Helper\InlinePicture::class,
            'pic'         => View\Helper\Pic::class,
            'img'         => View\Helper\Img::class,
            'pictures'    => View\Helper\Pictures::class,
            'moderMenu'   => View\Helper\ModerMenu::class,
            'count'       => View\Helper\Count::class,
        ],
        'factories' => [
            'mainMenu' => function($sm) {
                return new View\Helper\MainMenu($sm->get(MainMenu::class));
            },
            'language' => function($sm) {
                return new View\Helper\Language($sm->get(Language::class));
            },
            'user' => function($sm) {
                $acl = $sm->get(Acl::class);
                return new View\Helper\User($acl);
            },
            'fileSize' => function($sm) {
                return new View\Helper\FileSize(
                    $sm->get(Language::class),
                    $sm->get(FileSize::class)
                );
            },
            'humanDate' => function($sm) {
                $language = $sm->get(Language::class);
                return new View\Helper\HumanDate($language->getLanguage());
            },
            'comments' => function($sm) {
                $view = $sm->get(Zend_View::class);
                $commentForm = $sm->get('CommentForm');
                return new View\Helper\Comments($view, $commentForm);
            },
            'userText' => function($sm) {
                $router = $sm->get('Router');
                return new View\Helper\UserText($router);
            },
            'imageStorage' => function($sm) {
                $imageStorage = $sm->get(Image\Storage::class);
                return new View\Helper\ImageStorage($imageStorage);
            },
        ]
    ],
    'translator' => [
        'locale' => 'ru',
        'fallbackLocale' => 'en',
        'translation_file_patterns' => [
            [
                'type'     => \Zend\I18n\Translator\Loader\PhpArray::class,
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.php'
            ]
        ],
    ],
    'db' => [
        'adapter' => 'PDO_MYSQL',
        'params' => [
            'host'     => '',
            'username' => '',
            'password' => '',
            'dbname'   => '',
            'charset'  => 'utf8'
        ],
        'isDefaultTableAdapter' => true,
        'defaultMetadataCache'  => 'fast',
        'params.driver_options.1002' => "set time_zone = 'UTC'"
    ],
    'service_manager' => [
        'factories' => [
            Image\Storage::class => function($sm) {
                $config = $sm->get('Config')['imageStorage'];
                $storage = new Image\Storage($config);

                $request = $sm->get('Request');
                if ($request instanceof \Zend\Http\Request) {
                    if ($request->getServer('HTTPS')) {
                        $storage->setForceHttps(true);
                    }
                }

                return $storage;
            },
            Service\UsersService::class => function($sm) {
                $config = $sm->get('Config');
                $translator = $sm->get('translator');
                $transport = $sm->get('MailTransport');
                return new Service\UsersService($config['users'], $config['hosts'], $translator, $transport);
            },
            Zend_Db_Adapter_Abstract::class => function($sm) {
                $config = $sm->get('Config');
                $resource = new Zend_Application_Resource_Db($config['db']);
                return $resource->init();
            },
            'session' => function($sm) {
                $config = $sm->get('Config');
                $resource = new Zend_Application_Resource_Session($config['session']);
                return $resource->init();
            },
            Service\TelegramService::class => function($sm) {
                $config = $sm->get('Config');
                $router  = $sm->get('HttpRouter');
                return new Service\TelegramService($config['telegram'], $router);
            },
            'translator' => \Zend\Mvc\I18n\TranslatorFactory::class,
            Zend_Cache_Manager::class => function($sm) {
                $config = $sm->get('Config');
                $resource = new Zend_Application_Resource_Cachemanager($config['cachemanager']);
                return $resource->init();
            },
            MainMenu::class => function($sm) {

                $router = $sm->get('HttpRouter');
                $language = $sm->get(Language::class);
                $cache = $sm->get('longCache');
                $request = $sm->get('Request');
                $config = $sm->get('Config');

                return new MainMenu($request, $router, $language, $cache, $config['hosts']);
            },
            Language::class => function($sm) {

                $request = $sm->get('Request');

                return new Language($request);
            },
            TextStorage\Service::class => function($sm) {
                $options = $sm->get('Config')['textstorage'];
                $options['dbAdapter'] = $sm->get(Zend_Db_Adapter_Abstract::class);
                return new TextStorage\Service($options);
            },
            Zend_View::class => function($sm) {
                return new Zend_View([
                    'scriptPath' => APPLICATION_PATH . '/modules/default/views/scripts/'
                ]);
            },
            'MailTransport' => function($sm) {
                $config = $sm->get('Config');
                $transport = new \Zend\Mail\Transport\Smtp();
                $transport->setOptions(
                    new \Zend\Mail\Transport\SmtpOptions(
                        $config['mail']['transport']['options']
                    )
                );

                return $transport;
            },
            Acl::class => Permissions\AclFactory::class,
            ExternalLoginServiceFactory::class => function($sm) {
                $config = $sm->get('Config');
                return new ExternalLoginServiceFactory($config['externalloginservice']);
            },
            FileSize::class => function($sm) {
                return new FileSize();
            }
        ],
        'aliases' => [
            'ZF\OAuth2\Provider\UserId' => Provider\UserId\OAuth2UserIdProvider::class
        ],
        'abstract_factories' => [
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            //'Zend\Form\FormAbstractServiceFactory',
        ],
        /*'services' => [),
        'factories' => [),
        'initializators' => [),
        'delegators' => [),
        'shared' => [)*/

    ],


    'session' => [
        'use_only_cookies'    => true,
        'gc_maxlifetime'      => 1440,
        'remember_me_seconds' => 86400,
        'saveHandler' => [
            'class' => "Project_Session_SaveHandler_DbTable",
            'options' => [
                'name'           => "session",
                'primary'        => "id",
                'modifiedColumn' => "modified",
                'dataColumn'     => "data",
                'lifetimeColumn' => "lifetime",
                'userIdColumn'   => "user_id"
            ]
        ]
    ],

    'telegram' => [
        'accessToken' => '',
        'token'       => '',
        'webhook'     => ''
    ],

    'twitter' => [
        'username' => '',
        'oauthOptions' => [
            'consumerKey'    => '',
            'consumerSecret' => ''
        ],
        'token' => [
            'oauth_token'        => '',
            'oauth_token_secret' => ''
        ]
    ],

    'hosts' => [
        'ru' => [
            'hostname' => 'www.autowp.ru',
            'timezone' => 'Europe/Moscow',
            'name'     => 'Русский',
            'flag'     => 'flag-RU',
            'cookie'   => '.autowp.ru'
        ],
        'en' => [
            'hostname' => 'en.wheelsage.org',
            'timezone' => 'Europe/London',
            'name'     => 'English (beta)',
            'flag'     => 'flag-GB',
            'cookie'   => '.wheelsage.org'
        ],
        'fr' => [
            'hostname' => 'fr.wheelsage.org',
            'timezone' => 'Europe/Paris',
            'name'     => 'Français (beta)',
            'flag'     => 'flag-FR',
            'cookie'   => '.wheelsage.org'
        ],
        'zh' => [
            'hostname' => 'zh.wheelsage.org',
            'timezone' => 'Asia/Beijing',
            'name'     => '中文 (beta)',
            'flag'     => 'flag-CN',
            'cookie'   => '.wheelsage.org'
        ]
    ],

    /*'acl' => [
        'cache'         => 'long',
        'cacheLifetime' => 3600
    ],*/

    'textstorage' => [
        'textTableName'     => 'textstorage_text',
        'revisionTableName' => 'textstorage_revision'
    ],

    'feedback' => [
        'from'     => 'no-reply@autowp.ru',
        'fromname' => 'robot autowp.ru',
        'to'       => 'autowp@gmail.com',
        'subject'  => 'AutoWP Feedback'
    ],

    'validators' => [
        'factories' => [
            Validator\Brand\NameNotExists::class => InvokableFactory::class,
            Validator\User\EmailExists::class => InvokableFactory::class,
            Validator\User\EmailNotExists::class => InvokableFactory::class,
            Validator\User\Login::class => InvokableFactory::class,
        ],
    ],
    'filters' => [
        'factories' => [
            Filter\SingleSpaces::class => InvokableFactory::class
        ],
    ],

    'externalloginservice' => [
        'vk' => [
            'clientId'     => '',
            'clientSecret' => ''
        ],
        'google-plus' => [
            'clientId'     => '',
            'clientSecret' => ''
        ],
        'twitter' => [
            'consumerKey'    => '',
            'consumerSecret' => ''
        ],
        'facebook' => [
            'clientId'     => '',
            'clientSecret' => '',
            'scope'        => ['public_profile', 'user_friends']
        ],
        'github' => [
            'clientId'     => '',
            'clientSecret' => ''
        ],
        'linkedin' => [
            'clientId'     => '',
            'clientSecret' => ''
        ]
    ],

    'view_helper_config' => [
        'flashmessenger' => [
            'message_open_format'      => '<div%s><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><ul><li>',
            'message_close_string'     => '</li></ul></div>',
            'message_separator_string' => '</li><li>'
        ]
    ]
];
