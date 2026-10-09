<?php

namespace App\Service;

use App\Entity\User as UserEntity;

interface UserInterface
{
    public function count();
    public function countGroups();
    public function get($id);
    public function getByUsername($username);
    public function getGroupByName($groupname);
    public function findAll();
    public function insert(UserEntity $user);
    public function createIfNotExists($username, $password, $email = '', $groups = []);
    public function createGroupIfNotExists($role, $desciption = '');
    public function update(UserEntity $user);
    public function delete(UserEntity $user);
    public function encodePassword($user, $password);
    public function getUserFromSession();
    public function getUsernameFromSession();
    public function checkMetadataAccess($metadata, $user = null, $ignoreRole = null);
    public function generatePassword($length = 10);
    public function getRoles();
}
