<?php
/**
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\UsersModule\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Zikula\UsersModule\Entity\RepositoryInterface\UserVerificationRepositoryInterface;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\UsersModule\Entity\UserVerificationEntity;

class UserVerificationRepository extends EntityRepository implements UserVerificationRepositoryInterface
{
    public function persistAndFlush(UserVerificationEntity $entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    public function removeAndFlush(UserVerificationEntity $entity)
    {
        $this->_em->remove($entity);
        $this->_em->flush($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function purgeExpiredRecords($daysOld)
    {
        // Expiration date/times, as with all date/times in the Users module, are stored as UTC.
        $staleRecordUTC = new \DateTime(null, new \DateTimeZone('UTC'));
        $staleRecordUTC->modify("-{$daysOld} days");
        $staleRecordUTCStr = $staleRecordUTC->format(UsersConstant::DATETIME_FORMAT);

        $qb = $this->createQueryBuilder('v');
        $and = $qb->expr()->andX()
            ->add($qb->expr()->eq('v.changetype', UsersConstant::VERIFYCHGTYPE_REGEMAIL))
            ->add($qb->expr()->isNotNull('v.created_dt'))
            ->add($qb->expr()->neq('v.created_dt', '0000-00-00 00:00:00'))
            ->add($qb->expr()->lt('v.created_dt', $staleRecordUTCStr));
        $qb->select('v')
            ->where($and);
        $staleVerificationRecords = $qb->getQuery()->getResult();

        $deletedUsers = [];
        if (!empty($staleVerificationRecords)) {
            foreach ($staleVerificationRecords as $staleVerificationRecord) {
                // delete user record
                $userRepo = $this->_em->getRepository('ZikulaUsersModule:UserEntity');
                $user = $userRepo->find($staleVerificationRecord['uid']);
                $deletedUsers[] = $user;
                $userRepo->removeAndFlush($user);

                // delete verification record
                $this->_em->remove($staleVerificationRecord);
            }
            $this->_em->flush();
        }

        return $deletedUsers;
    }

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * Removes a record from the users_verifychg table for a specified uid and changetype.
     *
     * @param integer $uid The uid of the verifychg record to remove. Required.
     * @param int|array $types The changetype(s) of the verifychg record to remove. If more
     *                         than one type is to be removed, use an array. Optional. If
     *                         not specifed, all verifychg records for the user will be
     *                         removed. Note: specifying an empty array will remove none.
     * @return array
     */
    public function resetVerifyChgFor($uid, $types = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->delete()
            ->where('v.uid = :uid')
            ->setParameter('uid', $uid);
        if (isset($types)) {
            $qb->andWhere($qb->expr()->in('v.changetype', ':changeType'))
                ->setParameter('changeType', $types);
        }
        $query = $qb->getQuery();

        return $query->getResult();
    }
}