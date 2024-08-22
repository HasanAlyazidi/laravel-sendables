<?php

class SendablesHelpers {
    /**
     * Get OTP model
     *
     * @return string
     */
    public static function getOtpModel()
    {
        $modelClass = config('sendables.otp.db.model');

        if (is_null($modelClass)) {
            throw new Exception('Please include OTP `model` in `otp` inside config/sendables.php');
        }

        return $modelClass;
    }

    /**
     * Get OTP's table name
     *
     * @return string
     */
    public static function getOtpTableName()
    {
        $tableName = config('sendables.otp.db.table');

        if (is_null($tableName)) {
            throw new Exception("Please include OTP's table name in `otp` inside config/sendables.php");
        }

        return $tableName;
    }

    /**
     * Get password model
     *
     * @return string
     */
    public static function getPasswordModel()
    {
        $modelClass = config('sendables.otp.providers.password.model');

        if (is_null($modelClass)) {
            throw new Exception('Please include password `model` in `otp` inside config/sendables.php');
        }

        return $modelClass;
    }

    /**
     * Add plus (+) in the beginning
     *
     * @param string $value
     * @return string
     */
    public static function addLeadingPlus($value)
    {
        return $value[0] === '+' ? $value : '+'.$value;
    }

    /**
     * Remove plus (+) from the beginning
     *
     * @param string $value
     * @return string
     */
    public static function removeLeadingPlus($value)
    {
        return ltrim($value, '+');
    }

    /**
     * Get app current locale
     *
     * @return string
     */
    public static function getCurrentLocale()
    {
        return config('app.locale');
    }
}
