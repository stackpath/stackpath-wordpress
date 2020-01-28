<?php

namespace StackPath\WordPress;

use StackPath\WordPress\Integration\WordPressInterface;

/**
 * Data to persist across admin_post actions
 *
 * @see https://codex.wordpress.org/Transients_API
 */
class TransientData
{
    const NAME = 'stackpath_transient_data';

    /**
     * Notices to display to the user
     *
     * @var Message[]
     */
    public $notices = [];

    /**
     * Errors to display to the user
     *
     * @var Message[]
     */
    public $errors = [];

    /**
     * Data to auto-populate into forms
     *
     * @var array
     */
    public $formData = [];

    /**
     * Build Transient data
     *
     * @param Message[] $notices
     * @param Message[] $errors
     * @param array $formData
     */
    public function __construct(array $notices = [], array $errors = [], array $formData = [])
    {
        $this->notices = $notices;
        $this->errors = $errors;
        $this->formData = $formData;
    }

    /**
     * Add a transient notice
     *
     * @param Message $notice
     */
    public function addNotice(Message $notice)
    {
        $this->notices[] = $notice;
    }

    /**
     * Add a transient error message
     *
     * @param Message $error
     */
    public function addError(Message $error)
    {
        $this->errors[] = $error;
    }

    /**
     * Add transient form data
     *
     * @param string $key
     * @param mixed $value
     */
    public function addFormData($key, $value)
    {
        $this->formData[$key] = $value;
    }

    /**
     * Retrieve transient data from WordPress
     *
     * @param WordPressInterface $wordPress
     * @return TransientData
     */
    public static function get(WordPressInterface $wordPress)
    {
        $data = $wordPress->getTransient(self::NAME);

        if (!is_array($data)) {
            return new self();
        }

        return new self($data['notices'], $data['errors'], $data['form_data']);
    }

    /**
     * Save transient data to WordPress
     *
     * @param WordPressInterface $wordPress
     */
    public function save(WordPressInterface $wordPress)
    {
        $wordPress->setTransient(self::NAME, [
            'notices' => $this->notices,
            'errors' => $this->errors,
            'form_data' => $this->formData,
        ]);
    }

    /**
     * Delete transient data from WordPress
     *
     * @param WordPressInterface $wordPress
     */
    public function delete(WordPressInterface $wordPress)
    {
        $wordPress->deleteTransient(self::NAME);
    }

    /**
     * Retrieve individual form data
     *
     * @param string $key
     * @return mixed|null
     */
    public function getFormData($key)
    {
        if (array_key_exists($key, $this->formData)) {
            return $this->formData[$key];
        }

        return null;
    }
}
