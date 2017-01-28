<?php
/**
 * User: Sanchit <dev@svift.io>
 * Date: 4/22/2016
 * Time: 10:56 PM
 */
namespace Minute\Model {

    use Illuminate\Support\Collection;

    class CollectionEx extends Collection {
        protected $metadata;

        public function __construct($items, $metadata = null) {
            parent::__construct($items);
            $this->setMetadata($metadata);
        }

        /**
         * @return mixed
         */
        public function getMetadata() {
            return $this->metadata;
        }

        /**
         * @param mixed $metadata
         */
        public function setMetadata($metadata) {
            $this->metadata = $metadata;
        }

        public function toArray() {
            $value    = parent::toArray();
            $metadata = $this->metadata;
            unset($metadata['conditions']);

            return ['metadata' => $metadata, 'items' => $value];
        }
    }
}