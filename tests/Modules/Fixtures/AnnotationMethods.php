<?php
namespace DAwaa\Tests\Modules\Fixtures;

class AnnotationMethods {

    /**
     * @expand detailed showDetailedInformation
     * @expand adress   doesntExistSoWontShow
     * @unique userId
     */
    public function fetchUsers() {

    }

    public function showDetailedInformation() {
    }

    /**
     * @expand detailed showDetailedInformation
     * @expand avatar   getAvatar
     * @expand related  showRelatedTopics
     */
    public function fetchUser() {
    }

    public function getAvatar() {}
    public function showRelatedTopics() {}
}
