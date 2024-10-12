<?php

namespace Kris\LaravelFormBuilder;

class Field
{
    // Simple fields
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const SELECT = 'select';
    public const CHOICE = 'choice';
    public const CHECKBOX = 'checkbox';
    public const RADIO = 'radio';
    public const PASSWORD = 'password';
    public const HIDDEN = 'hidden';
    public const FILE = 'file';
    public const STATIC = 'static';
    //Date time fields
    public const DATE = 'date';
    public const DATETIME_LOCAL = 'datetime-local';
    public const MONTH = 'month';
    public const TIME = 'time';
    public const WEEK = 'week';
    //Special Purpose fields
    public const COLOR = 'color';
    public const SEARCH = 'search';
    public const IMAGE = 'image';
    public const EMAIL = 'email';
    public const URL = 'url';
    public const TEL = 'tel';
    public const NUMBER = 'number';
    public const RANGE = 'range';
    public const ENTITY = 'entity';
    public const FORM = 'form';
    //Buttons
    public const BUTTON_SUBMIT = 'submit';
    public const BUTTON_RESET = 'reset';
    public const BUTTON_BUTTON = 'button';
}
