<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 6/13/2016
 * Time: 6:18 PM
 */
namespace Minute\Event {

    use Minute\View\Redirection;

    class RedirectEvent extends Event {
        const REDIRECT = "redirect";
        /**
         * @var Redirection
         */
        private $redirection;

        /**
         * RedirectEvent constructor.
         *
         * @param Redirection $redirection
         */
        public function __construct(Redirection $redirection) {
            $this->redirection = $redirection;
        }

        /**
         * @return Redirection
         */
        public function getRedirection(): Redirection {
            return $this->redirection;
        }

        /**
         * @param Redirection $redirection
         *
         * @return RedirectEvent
         */
        public function setRedirection(Redirection $redirection): RedirectEvent {
            $this->redirection = $redirection;

            return $this;
        }

    }
}