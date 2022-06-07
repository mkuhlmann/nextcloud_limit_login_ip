<?php

namespace OCA\LimitLoginIp\AppInfo;

use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserLoggedInEvent;

class Application extends App
{

    public function __construct()
    {
        parent::__construct('limit_login_ip');
        /** @var IEventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);

        $dispatcher->addListener(BeforeUserLoggedInEvent::class, function (BeforeUserLoggedInEvent $event) {
            /** @var IUserManager */
            $userManager = $this->getContainer()->get(IUserManager::class);

            $users = $userManager->search($event->getUsername());

            if (count($users) > 0) {
                if(!$this->isLoginAllowed(array_values($users)[0])) {
                    $this->denyLoginRequest();
                }
            }


        });
    }

    private function isIpv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    private function isIpv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * @param $ip
     * @param $range
     * @return bool
     * @copyright https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5/594134#594134
     * @copyright (IPv4) https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5/594134#594134
     * @copyright (IPv6) MW. https://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet via 
     */
    private function matchCidr($ip, $range)
    {
        list($subnet, $bits) = explode('/', $range);

        if ($this->isIpv4($ip) && $this->isIpv4($subnet)) {
            if ($bits === '') {
                $bits = 32;
            }
            $mask = -1 << (32 - $bits);

            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $subnet &= $mask;
            return ($ip & $mask) === $subnet;
        }

        if ($this->isIpv6($ip) && $this->isIPv6($subnet)) {
            $subnet = inet_pton($subnet);
            $ip = inet_pton($ip);

            $binMask = str_repeat("f", $bits / 4);
            switch ($bits % 4) {
                case 0:
                    break;
                case 1:
                    $binMask .= "8";
                    break;
                case 2:
                    $binMask .= "c";
                    break;
                case 3:
                    $binMask .= "e";
                    break;
            }

            $binMask = str_pad($binMask, 32, '0');
            $binMask = pack("H*", $binMask);

            if (($ip & $binMask) === $subnet) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isLoginAllowed(IUser $user)
    {
        /** @var IRequest */
        $request = $this->getContainer()->get(IRequest::class);
        /** @var IConfig */
        $configManager = $this->getContainer()->get(IConfig::class);
        /** @var IGroupManager */
        $groupManager = $this->getContainer()->get(IGroupManager::class);

        $config = $configManager->getSystemValue('limit_login_ip_groups', []);

        $groups = array_keys($groupManager->getUserGroups($user));

        foreach ($groups as $group) {
            if (array_key_exists($group, $config)) {
                $ips = $config[$group];
                foreach ($ips as $range) {
                    if ($this->matchCidr($request->getRemoteAddress(), $range)) {
                        return true;
                    }
                }
                return false;
            }
        }
        return true;
    }

    public function denyLoginRequest()
    {
        /** @var IURLGenerator */
        $urlGenerator = $this->getContainer()->get(IURLGenerator::class);
        
        /** @var IRequest */
        $request = $this->getContainer()->get(IRequest::class);

        // Web UI
        if ($request->getRequestUri() === $urlGenerator->linkToRoute('core.login.showLoginForm')) {
            $url = $urlGenerator->linkToRouteAbsolute('limit_login_ip.LoginDenied.showErrorPage');
            header('Location: ' . $url);
            exit();
        }

        // All other clients
        http_response_code(403);
        exit();
    }
}
