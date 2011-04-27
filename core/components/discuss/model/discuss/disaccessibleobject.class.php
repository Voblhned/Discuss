<?php
/**
 * @package discuss
 */
class disAccessibleObject extends modAccessibleObject {

    public function checkPolicy($criteria, $targets = null) {
    //var_dump($criteria); var_dump($this->get('id'));
        if ($criteria && $this->xpdo instanceof modX && $this->xpdo->getSessionState() == modX::SESSION_STATE_INITIALIZED) {
            if (!is_array($criteria) && is_scalar($criteria)) {
                $criteria = array("{$criteria}" => true);
            }
            $policy = $this->findPolicy();
            if (!empty($policy)) {
                $principal = $this->xpdo->discuss->user->getAttributes($targets,'');
                if (!empty($principal)) {
                    foreach ($policy as $policyAccess => $access) {
                        foreach ($access as $targetId => $targetPolicy) {
                            foreach ($targetPolicy as $policyIndex => $applicablePolicy) {
                                if ($this->xpdo->getDebug() === true)
                                    $this->xpdo->log(xPDO::LOG_LEVEL_DEBUG, 'target pk='. $this->getPrimaryKey() .'; evaluating policy: ' . print_r($applicablePolicy, 1) . ' against principal for user id=' . $this->xpdo->getLoginUserID() .': ' . print_r($principal[$policyAccess], 1));
                                $principalPolicyData = array();
                                $principalAuthority = 9999;
                                if (isset($principal[$policyAccess][$targetId]) && is_array($principal[$policyAccess][$targetId])) {
                                    foreach ($principal[$policyAccess][$targetId] as $acl) {
                                        $principalAuthority = intval($acl['authority']);
                                        $principalPolicyData = $acl['policy'];
                                        $principalId = $acl['principal'];
                                        if ($applicablePolicy['principal'] == $principalId) {
                                            if ($principalAuthority <= $applicablePolicy['authority']) {
                                                if (!$applicablePolicy['policy']) {
                                                    return true;
                                                }
                                                $matches = array_intersect_assoc($principalPolicyData, $applicablePolicy['policy']);
                                                if ($matches) {
                                                    if ($this->xpdo->getDebug() === true)
                                                        $this->xpdo->log(modX::LOG_LEVEL_DEBUG, 'Evaluating policy matches: ' . print_r($matches, 1));
                                                    $matched = array_diff_assoc($criteria, $matches);
                                                     if (empty($matched)) {
                                                        return true;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return false;
            }
        }
        return true;
    }
}