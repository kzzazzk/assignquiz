<?php
class aiassign_plugin_header implements renderable {
    /** @var assign_plugin $plugin */
    public $plugin = null;

    /**
     * Header for a single plugin
     *
     * @param aiassign_plugin $plugin
     */
    public function __construct(aiassign_plugin $plugin) {
        $this->plugin = $plugin;
    }
}
class aiassign_submission_plugin_submission implements renderable {
    /** @var int SUMMARY */
    const SUMMARY                = 10;
    /** @var int FULL */
    const FULL                   = 20;

    /** @var assign_submission_plugin $plugin */
    public $plugin = null;
    /** @var stdClass $submission */
    public $submission = null;
    /** @var string $view */
    public $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    public $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    public $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    public $returnparams = array();

    /**
     * Constructor
     * @param aiassign_submission_plugin $plugin
     * @param stdClass $submission
     * @param string $view one of submission_plugin::SUMMARY, submission_plugin::FULL
     * @param int $coursemoduleid - the course module id
     * @param string $returnaction The action to return to the current page
     * @param array $returnparams The params to return to the current page
     */
    public function __construct(aiassign_submission_plugin $plugin,
                                stdClass $submission,
                                                         $view,
                                                         $coursemoduleid,
                                                         $returnaction,
                                                         $returnparams) {
        $this->plugin = $plugin;
        $this->submission = $submission;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}

