<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amalyuhin
 * Date: 30.01.13
 * Time: 12:31
 * To change this template use File | Settings | File Templates.
 */

namespace Wealthbot\ClientBundle\Model;


class AccountGroup
{
    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var array $_groups
     */
    static protected $_groups;

    // ENUM values name column
    const GROUP_FINANCIAL_INSTITUTION = 'financial_institution';
    const GROUP_DEPOSIT_MONEY = 'deposit_money';
    const GROUP_OLD_EMPLOYER_RETIREMENT = 'old_employer_retirement';
    const GROUP_EMPLOYER_RETIREMENT = 'employer_retirement';

    /**
     * Set name
     *
     * @param $name
     * @return AccountGroup
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if (!in_array($name, self::getGroupChoices())) {
            throw new \InvalidArgumentException(
                sprintf('Invalid value for account_groups.name column : %s.', $name)
            );
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get array ENUM values name column
     *
     * @static
     * @return array
     */
    static public function getGroupChoices()
    {
        // Build $_typeValues if this is the first call
        if (self::$_groups == null) {
            self::$_groups = array();
            $oClass = new \ReflectionClass('\Wealthbot\ClientBundle\Model\AccountGroup');
            $classConstants = $oClass->getConstants();
            $constantPrefix = "GROUP_";
            foreach ($classConstants as $key => $val) {
                if (substr($key, 0, strlen($constantPrefix)) === $constantPrefix) {
                    self::$_groups[$val] = $val;
                }
            }
        }

        return self::$_groups;
    }
}