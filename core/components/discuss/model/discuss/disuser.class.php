<?php
/**
 * @package discuss
 */
class disUser extends modPrincipal {
    const INACTIVE = 0;
    const ACTIVE = 1;
    const UNCONFIRMED = 2;
    const BANNED = 3;
    const AWAITING_MODERATION = 4;


    public function loadAttributes($target, $context = '',$reload = false) {
        if ($target != 'disBoardAccess') return;
        $context = !empty($context) ? $context : $this->xpdo->context->get('key');
        if ($this->_attributes === null) {
            $this->_attributes = array();
            if (isset($_SESSION["modx.user.{$this->xpdo->user->id}.attributes"])) {
                $this->_attributes = $_SESSION["modx.user.{$this->xpdo->user->id}.attributes"];
            }
        }
        //unset($_SESSION["modx.user.{$this->xpdo->user->id}.attributes"]['web']['disBoardAccess']);
        //var_dump($_SESSION["modx.user.{$this->xpdo->user->id}.attributes"]); die();
        //$reload = true;
        if (!isset($this->_attributes[$context])) $this->_attributes[$context] = array();
        if (empty($this->_attributes[$context][$target]) || $reload) {
            $accessTable = $this->xpdo->getTableName($target);
            $policyTable = $this->xpdo->getTableName('modAccessPolicy');
            $memberTable = $this->xpdo->getTableName('modUserGroupMember');
            $memberRoleTable = $this->xpdo->getTableName('modUserGroupRole');
            if ($this->get('id') > 0) {
                switch ($target) {
                    case 'disBoardAccess' :
                        $boards = array();
                        $sql = "SELECT acl.target, acl.principal, mr.authority, acl.policy, p.data FROM {$accessTable} acl " .
                                "LEFT JOIN {$policyTable} p ON p.id = acl.policy " .
                                "LEFT JOIN {$memberTable} mug ON acl.principal_class = 'modUserGroup' " .
                                //"AND (acl.context_key = :context OR acl.context_key IS NULL OR acl.context_key = '') " .
                                //"AND mug.member = :principal " .
                                "AND mug.user_group = acl.principal " .
                                "LEFT JOIN {$memberRoleTable} mr ON mr.id = mug.role " .
                                "AND mr.authority <= acl.authority " .
                                'WHERE
                                    mug.member = :principal
                                    OR acl.principal = 0
                                ' .
                                "ORDER BY acl.target, acl.principal, mr.authority, acl.policy";
                        $bindings = array(
                            ':principal' => $this->xpdo->user->get('id'),
                            //':context' => $context,
                        );
                        $query = new xPDOCriteria($this->xpdo, $sql, $bindings);
                        if ($query->stmt && $query->stmt->execute()) {
                            while ($row = $query->stmt->fetch(PDO::FETCH_ASSOC)) {
                                $this->_attributes[$context][$target][$row['target']][] = array(
                                    'principal' => $row['principal'],
                                    'authority' => $row['authority'],
                                    'policy' => $row['data'] ? $this->xpdo->fromJSON($row['data'], true) : array(),
                                );
                                $boards[$row['target']]= $row['target'];
                            }
                        }
                        //echo '<pre>';print_r($boards); die();
                        $_SESSION['modx.user.'.$this->xpdo->user->get('id').'.disBoard'] = array(
                            $context => array_values($boards),
                         );
                        break;
                    default :
                        break;
                }
            } else {
                switch ($target) {
                    case 'disBoardAccess' :
                        $boards = array();
                        $sql = "SELECT acl.target, acl.principal, 0 AS authority, acl.policy, p.data FROM {$accessTable} acl " .
                                "LEFT JOIN {$policyTable} p ON p.id = acl.policy " .
                                "WHERE acl.principal_class = 'modUserGroup' " .
                                "AND acl.principal = 0 " .
                                //"AND (acl.context_key = :context OR acl.context_key IS NULL OR acl.context_key = '') " .
                                "ORDER BY acl.target, acl.principal, acl.authority, acl.policy";
                        $bindings = array(
                            ':context' => $context
                        );
                        $query = new xPDOCriteria($this->xpdo, $sql, $bindings);
                        if ($query->stmt && $query->stmt->execute()) {
                            while ($row = $query->stmt->fetch(PDO::FETCH_ASSOC)) {
                                $this->_attributes[$context][$target][$row['target']][] = array(
                                    'principal' => 0,
                                    'authority' => $row['authority'],
                                    'policy' => $row['data'] ? $this->xpdo->fromJSON($row['data'], true) : array(),
                                );
                                $boards[$row['target']]= $row['target'];
                            }
                        }
                        var_dump($boards); die();

                        $_SESSION['modx.user.'.$this->xpdo->user->get('id').'.disBoard'] = array(
                            $context => array_values($boards),
                         );
                        break;
                    default :
                        break;
                }
            }
            if (!isset($this->_attributes[$context][$target])) {
                $this->_attributes[$context][$target] = array();
            }
            $_SESSION["modx.user.{$this->xpdo->user->id}.attributes"] = array_merge($_SESSION["modx.user.{$this->xpdo->user->id}.attributes"],$this->_attributes);
        }
    }
    /**
     * Gets the avatar URL for this user, depending on the avatar service.
     * @return string
     */
    public function getAvatarUrl() {
        $avatarUrl = '';

        $avatarService = $this->get('avatar_service');
        $avatar = $this->get('avatar');
        if (!empty($avatar) || !empty($avatarService)) {
            if (!empty($avatarService)) {
                if ($avatarService == 'gravatar') {
                    $avatarUrl = $this->xpdo->getOption('discuss.gravatar_url',null,'http://www.gravatar.com/avatar/').md5($this->get('email'));
                }
            } else {
                $avatarUrl = $this->xpdo->getOption('discuss.files_url').'/profile/'.$this->get('user').'/'.$this->get('avatar');
            }
        }
        return $avatarUrl;
    }

    public function parseSignature() {
        $message = $this->get('signature');
        if (!empty($message)) {
            $message = str_replace(array('&#91;','&#93;'),array('[',']'),$message);

            /* Check custom content parser setting */
            if ($this->xpdo->getOption('discuss.use_custom_post_parser',null,false)) {
                /* Load custom parser */
                $parsed = $this->xpdo->invokeEvent('OnDiscussPostCustomParser', array(
                        'content' => &$message,
                ));
                if (is_array($parsed)) {
                    foreach ($parsed as $msg) {
                        if (!empty($msg)) {
                            $message = $msg;
                        }
                    }
                } else if (!empty($parsed)) {
                    $message = $parsed;
                }
            } else if (true) {
                //$message = str_replace(array('<br/>','<br />','<br>'),'',$message);
                $message = $this->_nl2br2($message);
                $message = $this->parseBBCode($message);
            }

            /* Allow for plugin to change content of posts after it has been parsed */
            $rs = $this->xpdo->invokeEvent('OnDiscussPostFetchContent',array(
                'content' => &$message,
            ));

            if (is_array($rs)) {
                foreach ($rs as $msg) {
                    if (!empty($msg)) {
                        $message = $msg;
                    }
                }
            } else if (!empty($rs)) {
                $message = $rs;
            }
            $message = $this->stripBBCode($message);
        }
        return $message;
    }

    private function _nl2br2($str) {
        $str = str_replace("\r", '', $str);
        return preg_replace('/(?<!>)\n/', "<br />\n", $str);
    }


    /**
     * Parse BBCode in post and return proper HTML. Supports SMF/Vanilla formats.
     *
     * @param $message The string to parse
     * @return string The parsed string with HTML instead of BBCode, and all code stripped
     */
    public function parseBBCode($message) {
        /* handle quotes better, to allow for citing */
        $message = $this->parseQuote($message);

        /* parse bbcode from vanilla/smf boards bbcode formats */
        $message = preg_replace("#\[b\](.*?)\[/b\]#si",'<b>\\1</b>',$message);
        $message = preg_replace("#\[i\](.*?)\[/i\]#si",'<i>\\1</i>',$message);
        $message = preg_replace("#\[u\](.*?)\[/u\]#si",'<u>\\1</u>',$message);
        $message = preg_replace("#\[s\](.*?)\[/s\]#si",'<s>\\1</s>',$message);

        $message = preg_replace("#\[quote\](.*?)\[/quote\]#si",'<blockquote>\\1</blockquote>',$message);
        $message = preg_replace("#\[cite\](.*?)\[/cite\]#si",'<blockquote>\\1</blockquote>',$message);
        $message = preg_replace("#\[code\](.*?)\[/code\]#si",'<div class="dis-code"><h5>Code</h5><pre>\\1</pre></div>',$message);
        $message = preg_replace("#\[hide\](.*?)\[/hide\]#si",'\\1',$message);
        $message = preg_replace("#\[url\]([^/]*?)\[/url\]#si",'<a href="http://\\1">\\1</a>',$message);
        $message = preg_replace("#\[url\](.*?)\[/url\]#si",'\\1',$message);
        $message = preg_replace("#\[url=[\"']?(.*?)[\"']?\](.*?)\[/url\]#si",'<a href="\\1">\\2</a>',$message);
        $message = preg_replace("#\[php\](.*?)\[/php\]#si",'<code>\\1</code>',$message);
        $message = preg_replace("#\[mysql\](.*?)\[/mysql\]#si",'<code>\\1</code>',$message);
        $message = preg_replace("#\[css\](.*?)\[/css\]#si",'<code>\\1</code>',$message);
        $message = preg_replace("#\[img=[\"']?(.*?)[\"']?\](.*?)\[/img\]#si",'<img src="\\1" alt="\\2" />',$message);
        $message = preg_replace("#\[img\](.*?)\[/img\]#si",'<img src="\\1" border="0" />',$message);
        $message = str_ireplace(array('[indent]', '[/indent]'), array('<div class="Indent">', '</div>'), $message);
        $message = preg_replace('#\[/?left\]#si', '', $message);

        /* strip all remaining bbcode */
        $message = $this->stripBBCode($message);
        /* strip MODX tags */
        $message = str_replace(array('[',']'),array('&#91;','&#93;'),$message);
        return $message;
    }

    public function stripBBCode($str) {
         $pattern = '|[[\/\!]*?[^\[\]]*?]|si';
         $replace = '';
         return preg_replace($pattern, $replace, $str);
    }
    
    /* get a count of # of messages */
    public function countUnreadMessages() {
        $c = $this->xpdo->newQuery('disThread');
        $c->innerJoin('disThreadUser','Users');
        $c->leftJoin('disThreadRead','Reads','Reads.user = '.$this->get('id').' AND disThread.id = Reads.thread');
        $c->where(array(
            'disThread.private' => true,
            'Users.user' => $this->get('id'),
            'Reads.thread:IS' => null,
        ));
        return $this->xpdo->getCount('disThread',$c);
    }

    public function clearCache() {
        if (!defined('DISCUSS_IMPORT_MODE')) {
            $this->xpdo->getCacheManager();
            $this->xpdo->cacheManager->delete('discuss/user/'.$this->get('id'));
        }
    }

    /**
     * Parse a bbcode quote tag and return result
     *
     * @param $message The string to parse
     * @return string The quoted message
     */
    public function parseQuote($message) {
        preg_match_all("#\[quote=?(.*?)[\"']?\](.*?)\[/quote\]#si",$message,$matches);
        if (!empty($matches)) {
            $quotes = array();
            $replace = array();
            $meta = array();
            $with = array();
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) { $replace[] = $match; }
                foreach ($matches[1] as $match) { $meta[] = $match; }
                foreach ($matches[2] as $match) { $with[] = $match; }
            }
            for ($i=0;$i<count($replace);$i++) {
                $auth = array();
                $mt = explode(' ',$meta[$i]);
                foreach ($mt as $m) {
                    if (empty($m)) continue;
                    $m = explode('=',$m);
                    switch ($m[0]) {
                        case 'author': $auth['user'] = $m[1]; break;
                        case 'date': $auth['date'] = $m[1]; break;
                        case 'link': $auth['link'] = $m[1]; break;
                    }
                }
                $cite = '';
                if (!empty($auth['user']) || !empty($auth['date'])) {
                    $cite = '<cite>Quote';
                    if (!empty($auth['user'])) $cite .= ' from: '.$auth['user'];
                    if (!empty($auth['date'])) $cite .= ' at '.strftime($this->xpdo->discuss->dateFormat,$auth['date']);
                    $cite .= '</cite>';
                }

                /* strip annoying starting br tags */
                $with[$i] = substr($with[$i],0,6) == '<br />' ? $with[$i] = substr($with[$i],6) : $with[$i];

                /* now insert our quote */
                $message = str_replace($replace[$i],$cite.'<blockquote>'.$with[$i].'</blockquote>',$message);
            }
        }
        return $message;
    }

    public function getUserGroups() {
        $groups = array();
        $this->getOne('User');
        if ($this->User) {
            $groups = $this->User->getUserGroups();

            $adminGroups = $this->xpdo->getOption('discuss.admin_groups',null,'Forum Administrator,Administrator');
            $adminGroups = explode(',',$adminGroups);
            if ($this->User->isMember($adminGroups)) {
                $this->isAdmin = true;
            }
        }
        return $groups;
    }
}