<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\PermissionsModule\Entity\RepositoryInterface;

interface PermissionRepositoryInterface
{
    public function getPermissionsByGroups(array $groups);
}
