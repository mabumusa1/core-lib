<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

/**
 * Interface UserTokenRepositoryInterface.
 */
interface UserTokenRepositoryInterface
{
    /**
     * @param string $signature
     *
     * @return UserToken
     */
    public function isSignatureUnique($signature);

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token);
}
