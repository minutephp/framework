<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 9/4/2016
 * Time: 2:14 AM
 */
namespace Minute\Event {

    use App\Model\MUserDatum;
    use App\Model\User;

    class UserEvent extends Event {
        /**
         * @var int
         */
        protected $user_id;
        /**
         * @var array
         */
        protected $userData;
        /**
         * @var User
         */
        protected $user;

        /**
         * UserEvent constructor.
         *
         * @param int $user_id
         * @param array $userData
         */
        public function __construct(int $user_id = 0, array $userData = []) {
            $this->user_id  = $user_id;
            $this->userData = $userData;
        }

        /**
         * @return User
         */
        public function getUser() {
            if (empty($this->user) && !empty($this->user_id)) {
                $this->user = User::find($this->user_id);
            }

            return $this->user;
        }

        /**
         * @param User $user
         *
         * @return UserEvent
         */
        public function setUser(User $user) {
            $this->user    = $user;
            $this->user_id = $user->user_id;

            return $this;
        }

        /**
         * @return array
         */
        public function getUserData(): array {
            if (empty($this->userData) && !empty($this->user_id)) {
                foreach (MUserDatum::where('user_id', '=', $this->user_id)->get() as $data) {
                    $this->userData[$data->key] = $data->value;
                }
            }

            return $this->userData;
        }

        /**
         * @param array $userData
         *
         * @return UserEvent
         */
        public function setUserData(array $userData): UserEvent {
            $this->userData = $userData;

            return $this;
        }

        /**
         * @return int
         */
        public function getUserId(): int {
            return $this->user_id;
        }

        /**
         * @param int $user_id
         *
         * @return UserEvent
         */
        public function setUserId(int $user_id): UserEvent {
            $this->user_id = $user_id;

            return $this;
        }
    }
}