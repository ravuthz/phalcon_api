<?php namespace My\Models;

use Phalcon\Mvc\Model;
// use Phalcon\Mvc\Model\Validator\Uniqueness;
// use Phalcon\Mvc\Model\Validator\InclusionIn;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Uniqueness;

class Notes extends Model {
    public function validation()
    {
        // $this->validate(
        //     new Uniqueness(
        //         [
        //             "field" => "name",
        //             "message" => "The note name is exists"
        //         ]
        //     )
        // );
        
        // Check if any messages have been produced
        // if ($this->validationHasFailed() === true) {
        //     return false;
        // }

        $validator = new Validation();

        $validator->add(
            "name",
            new PresenceOf([
                "message" => "The note's name is required",
            ])
        );

        $validator->add(
            "name",
            new Uniqueness([
                "message" => "The note's name is exists"
            ])
        );

        return $this->validate($validator);
    }
}