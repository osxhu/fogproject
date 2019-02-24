<?php
/**
 * Access Control plugin
 *
 * PHP version 7
 *
 * @category AccessControlManagement
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Access Control plugin
 *
 * @category AccessControlManagement
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AccessControlManagement extends FOGPage
{
    /**
     * The node of this page.
     *
     * @var string
     */
    public $node = 'accesscontrol';
    /**
     * Constructor
     *
     * @param string $name The name for the page.
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = 'Role Management';
        parent::__construct($this->name);
        $this->headerData = [
            _('Role Name'),
            _('Role Description')
        ];
        $this->attributes = [
            [],
            []
        ];
    }
    /**
     * Create new role.
     *
     * @return void
     */
    public function add()
    {
        $this->title = _('Create New Role');

        $role = filter_input(INPUT_POST, 'role');
        $description = filter_input(INPUT_POST, 'description');

        $labelClass = 'col-sm-3 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'role',
                _('Role Name')
            ) => self::makeInput(
                'form-control rolename-input',
                'role',
                _('Access Control Name'),
                'text',
                'role',
                $role,
                true
            ),
            self::makelabel(
                $labelClass,
                'description',
                _('Role Description')
            ) => self::makeTextarea(
                'form-control roledescription-input',
                'description',
                _('Role Description'),
                'description',
                $description
            )
        ];

        $buttons = self::makeButton(
            'send',
            _('Create'),
            'btn btn-primary pull-right'
        );

        self::$HookManager->processEvent(
            'ACCESSCONTROL_ADD_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'AccessControl' => self::getClass('AccessControl')
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'role-create-form',
            $this->formAction,
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid" id="role-create">';
        echo '<div class="box-body">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Create New Role');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo $buttons;
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Create new role.
     *
     * @return void
     */
    public function addModal()
    {
        $role = filter_input(INPUT_POST, 'role');
        $description = filter_input(INPUT_POST, 'description');

        $labelClass = 'col-sm-3 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'role',
                _('Role Name')
            ) => self::makeInput(
                'form-control rolename-input',
                'role',
                _('Access Control Name'),
                'text',
                'role',
                $role,
                true
            ),
            self::makelabel(
                $labelClass,
                'description',
                _('Role Description')
            ) => self::makeTextarea(
                'form-control roledescription-input',
                'description',
                _('Role Description'),
                'description',
                $description
            )
        ];

        self::$HookManager->processEvent(
            'ACCESSCONTROL_ADD_FIELDS',
            [
                'fields' => &$fields,
                'AccessControl' => self::getClass('AccessControl')
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'create-form',
            '../management/index.php?node=accesscontrol&sub=add',
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo $rendered;
        echo '</form>';
    }
    /**
     * Add post.
     *
     * @return void
     */
    public function addPost()
    {
        header('Content-type: application/json');
        self::$HookManger->processEvent('ACCESSCONTROL_ADD_POST');
        $role = trim(
            filter_input(INPUT_POST, 'role')
        );
        $description = trim(
            filter_input(INPUT_POST, 'description')
        );

        $serverFault = false;
        try {
            $exists = self::getClass('AccessControlManager')
                ->exists($role);
            if ($exists) {
                throw new Exception(
                    _('A role already exists with this name!')
                );
            }
            $AccessControl = self::getClass('AccessControl')
                ->set('name', $role)
                ->set('description', $description);
            if (!$AccessControl->save()) {
                $serverFault = true;
                throw new Exception(_('Add role failed!'));
            }
            $code = HTTPResponseCodes::HTTP_CREATED;
            $hook = 'ACCESSCONTROL_ADD_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Role added!'),
                    'title' => _('Role Create Success')
                ]
            );
        } catch (Exception $e) {
            $code = (
                $serverFault ?
                HTTPResponseCodes::HTTP_INTERNAL_SERVER_ERROR :
                HTTPResponseCodes::HTTP_BAD_REQUEST
            );
            $hook = 'ACCESSCONTROL_ADD_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Role Create Fail')
                ]
            );
        }
        //header(
        //    'Location: ../management/index.php?node=accesscontrol&sub=edit&id='
        //    . $AccessControl->get('id')
        //);
        self::$HookManager->processEvent(
            $hook,
            [
                'AccessControl' => &$AccessControl,
                'hook' => &$hook,
                'code' => &$code,
                'msg' => &$msg,
                'serverFault' => &$serverFault
            ]
        );
        http_response_Code($code);
        unset($AccessControl);
        echo $msg;
        exit;
    }
    /**
     * Displays the access control general tab.
     *
     * @return void
     */
    public function roleGeneral()
    {
        $role = (
            filter_input(INPUT_POST, 'role') ?:
            $this->obj->get('name')
        );
        $description = (
            filter_input(INPUT_POST, 'description') ?:
            $this->obj->get('description')
        );

        $labelClass = 'col-sm-3 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'role',
                _('Role Name')
            ) => self::makeInput(
                'form-control rolename-input',
                'role',
                _('Access Control Name'),
                'text',
                'role',
                $role,
                true
            ),
            self::makelabel(
                $labelClass,
                'description',
                _('Role Description')
            ) => self::makeTextarea(
                'form-control roledescription-input',
                'description',
                _('Role Description'),
                'description',
                $description
            )
        ];

        $buttons = self::makeButton(
            'general-send',
            _('Update'),
            'btn btn-primary pull-right'
        );
        $buttons .= self::makeButton(
            'general-delete',
            _('Delete'),
            'btn btn-danger pull-left'
        );

        self::$HookManager->processEvent(
            'ACCESSCONTROL_GENERAL_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'AccessControl' => &$this->obj
            ]
        );

        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'role-general-form',
            self::makeTabUpdateURL(
                'role-general',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid">';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo $buttons;
        echo $this->deleteModal();
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Updates the access control general element.
     *
     * @return void
     */
    public function roleGeneralPost()
    {
        $role = trim(
            filter_input(INPUT_POST, 'role')
        );
        $description = trim(
            filter_input(INPUT_POST, 'description')
        );

        $exists = self::getClass('AccessControlManager')
            ->exists($role);
        if ($role != $this->obj->get('name')
            && $exists
        ) {
            throw new Exception(
                _('A role with this name already exists!')
            );
        }
        $this->obj
            ->set('name', $role)
            ->set('description', $description);
    }
    /**
     * Present the users tab.
     *
     * @return void
     */
    public function roleUsers()
    {
        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'role-users',
                $this->obj->get('id')
            )
            . '" ';

        $buttons = self::makeButton(
            'users-add',
            _('Add selected'),
            'btn btn-primary pull-right',
            $props
        );
        $buttons .= self::makeButton(
            'users-remove',
            _('Remove selected'),
            'btn btn-danger pull-left',
            $props
        );

        $this->headerData = [
            _('User Name'),
            _('Associated')
        ];
        $this->attributes = [
            [],
            ['width' => 16]
        ];

        echo '<!-- Users -->';
        echo '<div class="box-group" id="users">';
        echo '<div class="box box-solid">';
        echo '<div id="updateusers" class="">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Role Users');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        $this->render(12, 'role-users-table', $buttons);
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo $this->assocDelModal('user');
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Update users.
     *
     * @return void
     */
    public function roleUserPost()
    {
        if (isset($_POST['updateusers'])) {
            $users = filter_input_array(
                INPUT_POST,
                [
                    'user' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $users = $users['user'];
            if (count($users ?: []) > 0) {
                $this->obj->addUser($users);
            }
        }
        if (isset($_POST['confirmdel'])) {
            $users = filter_input_array(
                INPUT_POST,
                [
                    'remitems' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $users = $users['remitems'];
            if (count($users ?: []) > 0) {
                $this->obj->removeUser($users);
            }
        }
    }
    /**
     * Preset the rules page.
     *
     * @return void
     */
    public function roleRules()
    {
        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'role-rules',
                $this->obj->get('id')
            )
            . '" ';

        $buttons = self::makeButton(
            'rules-add',
            _('Add selected'),
            'btn btn-primary pull-right',
            $props
        );
        $buttons .= self::makeButton(
            'rules-remove',
            _('Remove selected'),
            'btn btn-danger pull-left',
            $props
        );

        $this->headerData = [
            _('Rule Name'),
            _('Rule Parent'),
            _('Rule Value'),
            _('Rule Node'),
            _('Associated')
        ];
        $this->attributes = [
            [],
            [],
            [],
            [],
            ['width' => 16]
        ];

        echo '<!-- Rules -->';
        echo '<div class="box-group" id="rules">';
        echo '<div class="box box-solid">';
        echo '<div id="updaterules" class="">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Role Rules');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        $this->render(12, 'role-rules-table', $buttons);
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo $this->assocDelModal('rule');
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Update rules.
     *
     * @return void
     */
    public function roleRulePost()
    {
        if (isset($_POST['updaterules'])) {
            $rules = filter_input_array(
                INPUT_POST,
                [
                    'rule' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $rules = $rules['rule'];
            if (count($rules ?: []) > 0) {
                $this->obj->addRule($rules);
            }
        }
        if (isset($_POST['confirmdel'])) {
            $rules = filter_input_array(
                INPUT_POST,
                [
                    'remitems' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $rules = $rules['remitems'];
            if (count($rules ?: []) > 0) {
                $this->obj->removeRule($rules);
            }
        }
    }
    /**
     * The edit element.
     *
     * @return void
     */
    public function edit()
    {
        $this->title = sprintf(
            '%s: %s',
            _('Edit'),
            $this->obj->get('name')
        );

        $tabData = [];

        // General
        $tabData[] = [
            'name' => _('General'),
            'id' => 'role-general',
            'generator' => function () {
                $this->roleGeneral();
            }
        ];

        // Associations
        $tabData[] = [
            'tabs' => [
                'name' => _('Associations'),
                'tabData' => [
                    [
                        'name' => _('Rule Association'),
                        'id' => 'role-rules',
                        'generator' => function () {
                            $this->roleRules();
                        }
                    ],
                    [
                        'name' => _('User Association'),
                        'id' => 'role-users',
                        'generator' => function () {
                            $this->roleUsers();
                        }
                    ]
                ]
            ]
        ];

        echo self::tabFields($tabData, $this->obj);
    }
    /**
     * Update the edit elements.
     *
     * @return void
     */
    public function editPost()
    {
        header('Content-type: application/json');
        self::$HookManager->processEvent(
            'ACCESSCONTROL_EDIT_POST',
            ['AccessControl' => &$this->obj]
        );

        $serverFault = false;
        try{
            global $tab;
            switch ($tab) {
            case 'role-general':
                $this->roleGeneralPost();
                break;
            case 'role-rules':
                $this->roleRulePost();
                break;
            case 'role-users':
                $this->roleUserPost();
            }
            if (!$this->obj->save()) {
                $serverFault = true;
                throw new Exception(_('Role update failed!'));
            }
            $code = HTTPResponseCodes::HTTP_ACCEPTED;
            $hook = 'ACCESSCONTROL_EDIT_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Role updated!'),
                    'title' => _('Role Update Success')
                ]
            );
        } catch (Exception $e) {
            $code = (
                $serverFault ?
                HTTPResponseCodes::HTTP_INTERNAL_SERVER_ERROR :
                HTTPResponseCodes::HTTP_BAD_REQUEST
            );
            $hook = 'ACCESSCONTROL_EDIT_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Role Update Fail')
                ]
            );
        }
        self::$HookManager->processEvent(
            $hook,
            [
                'AccessControl' => &$this->obj,
                'hook' => &$hook,
                'code' => &$code,
                'msg' => &$msg,
                'serverFault' => &$serverFault
            ]
        );
        http_response_code($code);
        echo $msg;
        exit;
    }
    /**
     * Gets the user list.
     *
     * @return void
     */
    public function getUsersList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $usersSqlStr = "SELECT `%s`,"
            . "IF(`ruaRoleID` = '"
            . $this->obj->get('id')
            . "','associated','dissociated') as `ruaRoleID`
            FROM `%s`
            LEFT OUTER JOIN `roleUserAssoc`
            ON `users`.`uID` = `roleUserAssoc`.`ruaUserID`
            %s
            %s
            %s";
        $usersFilterStr = "SELECT COUNT(`%s`)
            FROM `%s`
            LEFT OUTER JOIN `roleUserAssoc`
            ON `users`.`uID` = `roleUserAssoc`.`ruaUserID`
            %s";
        $usersTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('UserManager')
            ->getColumns() as $common => &$real
        ) {
            if ('id' == $common) {
                $tableID = $real;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        $columns[] = [
            'db' => 'ruaRoleID',
            'dt' => 'association'
        ];
        echo json_encode(
            FOGManagerController::simple(
                $pass_vars,
                'users',
                $tableID,
                $columns,
                $usersSqlStr,
                $usersFilterStr,
                $usersTotalStr
            )
        );
        exit;
    }
    /**
     * Gets the rules list.
     *
     * @return void
     */
    public function getRulesList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $rulesSqlStr = "SELECT `%s`,"
            . "IF(`rraRoleID` = '"
            . $this->obj->get('id')
            . "','associated','dissociated') as `rraRoleID`
            FROM `%s`
            LEFT OUTER JOIN `roleRuleAssoc`
            ON `rules`.`ruleID` = `roleRuleAssoc`.`rraRuleID`
            %s
            %s
            %s";
        $rulesFilterStr = "SELECT COUNT(`%s`)
            FROM `%s`
            LEFT OUTER JOIN `roleRuleAssoc`
            ON `rules`.`ruleID` = `roleRuleAssoc`.`rraRuleID`
            %s";
        $rulesTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('AccessControlRuleManager')
            ->getColumns() as $common => &$real
        ) {
            if ('id' == $common) {
                $tableID = $real;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        $columns[] = [
            'db' => 'rraRoleID',
            'dt' => 'association'
        ];
        echo json_encode(
            FOGManagerController::simple(
                $pass_vars,
                'rules',
                $tableID,
                $columns,
                $rulesSqlStr,
                $rulesFilterStr,
                $rulesTotalStr
            )
        );
        exit;
    }
}
