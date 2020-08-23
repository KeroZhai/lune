<?php

namespace app\core\controller;

use app\core\util\AuthUtils;
use app\core\util\ReflectionUtils;
use app\core\repository\Repository;
use app\core\response\StatusResult;

/**
 * 一个抽象的控制器, 没有实际的接口方法
 * 提供了简单的认证和验证角色功能以及数据库事务
 */
class AbstractController {

    /**
     * 前置拦截器, 验证是否登录
     * 只对具有 @RequiresAuthentication 标记的接口方法生效
     * 
     * @Before
     * @WithTag @RequiresAuthentication
     */
    function authenticate() {
        if (!AuthUtils::isLoggedIn()) {
            return StatusResult::status(401);
        }
    }

    /**
     * 前置拦截器, 验证是否具有指定的角色
     * 只对具有 @RequiresRole 标记的接口方法生效
     * 
     * @Before
     * @WithTag @RequiresRole
     */
    function authRole($__method__) {
        $requiresRole = ReflectionUtils::getDocCommentTag($__method__->getDocComment(), "@RequiresRole");
        $roles = AuthUtils::getRoles();
        if (count($roles) == 0) {
            throw new \Exception("No roles set for current user");
        } else if (!in_array($requiresRole, $roles)) {
            return StatusResult::status(403);
        }
    }

    /**
     * 前置拦截器, 自动开启事务
     * 只对具有 @Transactional 标记的接口方法生效
     * 
     * @Before
     * @WithTag @Transactional
     * @Injected repository
     */
    function beginTransaction(Repository $repository) {
        $repository->beginTransaction();
    }

    /**
     * 后置拦截器, 自动提交事务
     * 只对具有 @Transactional 标记的接口方法生效
     * 
     * @After
     * @WithTag @Transactional
     * @Injected repository
     */
    function commitTransaction(Repository $repository) {
        $repository->commit();
    }

}
