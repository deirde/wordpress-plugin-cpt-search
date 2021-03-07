<?php

declare(strict_types=1);

namespace david1 {
    !defined('ABSPATH') && exit;

    class CustomSearchCustomPostType
    {
        protected $setup = [
            'postType' => 'my-custom-post-type',
            'searchKeyword' => 'y',
            'searchResultsUrl' => 'my-custom-post-type-search-results-page-slug',
            'singleDetailsUrl' => 'my-custom-post-type-detail-page-slug',
            'searchFormWrapperId' => 'david1-custom-search-my-custom-post-type-form-wrapper',
            'searchResultsWrapperId' => 'david1-custom-search-my-custom-post-type-results-wrapper',
            'customFieldsToShowInDetailsPage' => [
                'custom_field_1' => 'Label custom field 1',
                'custom_field_2' => 'Label custom field 2',
                'custom_field_3' => 'Label custom field 3',
                'custom_field_4' => 'Label custom field 4',
                'custom_field_5' => 'Label custom field 5'
            ],

            // Shortcodes
            'searchFormShortcodeName' => 'cpt_search_form',
            'searchResultsShortcodeName' => 'cpt_search_results',
            'singleDetailsShortcodeName' => 'cpt_single_details'
        ];
        public $params = [
            'searchKeyword' => '',
        ];
        public $searchForm;
        protected $isSearchResults = false;
        protected $isSingle = false;
        protected $searchResultsData = [];
        protected $singleDetailsData;

        public static function init()
        {
            $class = __CLASS__;
            new $class;
        }

        public function __construct()
        {
            $this->setIsSearchResults();
            $this->setIsSingle();
            $this->redirector();
            $this->setSearchParams();
            $this->setSearchForm();
            $this->addSearchFormShortcode();
            if (!$this->getIsSingle()) {
                $this->setSearchResultsData();
                $this->addSearchResultsShortcode();
            } else {
                $this->setSingleDetails();
                $this->addSingleShortcode();
            }
            $this->baseCss();
        }

        protected function baseCss()
        {
            wp_register_style($this->setup['searchFormWrapperId'], false);
            wp_enqueue_style($this->setup['searchFormWrapperId']);
            wp_add_inline_style($this->setup['searchFormWrapperId'], '
                #' . $this->setup['searchResultsWrapperId'] . '
                    {float:left;margin-bottom:72px}
                #' . $this->setup['searchResultsWrapperId'] . ' a
                    {display:block;float:left;width: 100%;margin-bottom:35px;text-decoration:none}
                #' . $this->setup['searchResultsWrapperId'] . ' a:hover p
                    {color:#000}
                #' . $this->setup['searchResultsWrapperId'] . ' a img
                    {float:left;border:1px solid #000;margin-right:15px}
                #' . $this->setup['searchResultsWrapperId'] . ' a h3
                    {font-size:17px;text-transform:uppercase;color:black}
                #' . $this->setup['searchResultsWrapperId'] . ' a p
                    {font-size:17px;line-height:17px}
                #' . $this->setup['searchResultsWrapperId'] . '-single h1
                    {margin-bottom:32px}
                #' . $this->setup['searchResultsWrapperId'] . '-single img
                    {width:50%,border:1px solid black;margin:0 0 12px}
            ');
            add_action($this->setup['searchFormWrapperId'] . '_hook', 'baseCss');
        }

        protected function getCurrentCleanUrl(): string
        {
            return str_replace('/', '', trim(strtok($_SERVER['REQUEST_URI'], '?')));
        }

        protected function setIsSearchResults(): void
        {
            if (isset($_REQUEST) && isset($_REQUEST['pt'])) {
                $this->isSearchResults =
                    ($_REQUEST['pt'] === $this->setup['postType']) &&
                    $_REQUEST[$this->setup['searchKeyword']];
            }
        }

        protected function getIsSearchResults(): bool
        {
            return $this->isSearchResults;
        }

        protected function setIsSingle(): void
        {
            $this->isSingle = $this->getCurrentCleanUrl() ===
                $this->setup['singleDetailsUrl'];
        }

        protected function getIsSingle(): bool
        {
            return $this->isSingle;
        }

        protected function redirector(): void
        {
            if (
                $this->getIsSearchResults() &&
                $this->getCurrentCleanUrl() !==
                str_replace('/', '', $this->setup['searchResultsUrl'])
            ) {
                header('Location: /' . $this->setup['searchResultsUrl'] . '?' .
                    $_SERVER['QUERY_STRING']);
                exit();
            }
        }

        protected function setSearchParams(): void
        {
            if ($this->getIsSearchResults()) {
                $this->params['searchKeyword'] =
                    filter_var($_REQUEST[$this->setup['searchKeyword']], FILTER_SANITIZE_STRING);
            }
        }

        protected function setSearchForm(): void
        {
            $this->searchForm = '
                <form role="search" method="get" id="' . $this->setup['searchFormWrapperId'] . '">
                    <div>
                        <label for="s">' . _('Search for:') . '</label>
                        <input type="text"
                            value="' . (isset($this->params['searchKeyword']) ?
                                $this->params['searchKeyword'] : '') . '"
                            name="' . $this->setup['searchKeyword'] . '"
                            id="' . $this->setup['searchKeyword'] . '"
                            placeholder="' . gettext('Name, Subject or Age Range') . '" />
                        <input type="hidden" value="1″ name="sentence" />
                        <input type="hidden" value="' . $this->setup['postType'] . '" name="pt" />
                        <input type="submit" value="Search" />
                    </div>
                </form>
            ';
        }

        public function getSearchForm(): string
        {
            return $this->searchForm;
        }

        protected function addSearchFormShortcode(): void
        {
            add_shortcode($this->setup['searchFormShortcodeName'],
                array($this, 'setSearchFormShortcode'));
        }

        public function setSearchFormShortcode($params): string
        {
            return $this->getSearchForm();
        }

        protected function setSearchResultsData(): void
        {
            $args = [
                'post_type' => $this->setup['postType'],
                'post_status' => 'publish',
                'order' => 'ASC',
                'posts_per_page' => -1,
            ];
            $posts = get_posts($args);
            $key = strtolower($this->params['searchKeyword']);
            foreach ($posts as $post) {
                $_ = get_object_vars($post);
                $title = strtolower($_['post_title']);
                $qualification = strtolower(strval(get_field(
                    'custom_field_1', $_['ID'], false)));
                $experience = strtolower(strval(get_field(
                    'custom_field_2', $_['ID'], false)));
                if (
                    strpos($title, $key) !== false ||
                    strpos($qualification, $key) !== false ||
                    strpos($experience, $key) !== false
                ) {
                    $this->searchResultsData[] = $_;
                }
            }
            wp_reset_query();
        }

        protected function getSearchResultsData(): array
        {
            return $this->searchResultsData;
        }

        protected function addSearchResultsShortcode(): void
        {
            add_shortcode($this->setup['searchResultsShortcodeName'],
                array($this, 'setSearchResultsShortcode'));
        }

        protected function getSearchResultsNoResultsText()
        {
            return gettext('No results found for your search criteria.</br>
                Please try again changing the search keyword');
        }

        public function setSearchResultsShortcode($params): string
        {
            if (!$this->getSearchResultsData()) {
                return $this->getSearchResultsNoResultsText();
            }
            $response = '<div id="' . $this->setup['searchResultsWrapperId'] . '">';
            foreach ($this->getSearchResultsData() as $post) {
                $customField1 = get_field('custom_field_1', $post['ID'], false);
                $postThumbnailId = get_post_thumbnail_id($post['ID']);
                $featuredImage = wp_get_attachment_image_url($postThumbnailId, 'small');
                if ($featuredImage) {
                    $response .= '
                    <a href="/' . $this->setup['singleDetailsUrl'] . '?' .
                        $post['post_name'] . '&id=' . $post['ID'] . '">
                        <img src="' . $featuredImage . '" width="150">
                        <div><h3>' . $post['post_title'] . '</h3>
                        <p>' . mb_strimwidth($customField1 ? strip_tags($customField1) : '',
                            0, 255, '...') . '</p></div></a>';
                }
            }
            $response .= '</div>';
            return $response;
        }

        protected function setSingleDetails(): void
        {
            $this->singleDetailsData = get_post($_REQUEST['id'], ARRAY_A);
        }

        protected function addSingleShortcode(): void
        {
            add_shortcode($this->setup['singleDetailsShortcodeName'],
                array($this, 'setSingleShortcode'));
        }

        public function setSingleShortcode($params): string
        {
            if (!$this->singleDetailsData) {
                header('Location: /');
                exit();
            }
            $post = $this->singleDetailsData;
            $postThumbnailId = get_post_thumbnail_id($post['ID']);
            $featuredImage = wp_get_attachment_image_url($postThumbnailId, 'large');
            $response = '
                <div id="' . $this->setup['searchResultsWrapperId'] . '-single">
                <h1>' . $post['post_title'] . '</h1>
                <img src="' . $featuredImage . '">
                <ul>';
            foreach ($this->customFieldsToShowInDetailsPage as $val => $label ) {
                if ($_ = get_field($val, $post['ID'], false)) {
                    $response .= '<li><h3>' . _($label) . '</h3><p>' . $_ . '</p></li>';
                }
            }
            $response .= '</ul></div>';
            return $response;
        }
    }
}
