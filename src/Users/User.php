<?php

namespace DigraphCMS\Users;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\A;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use Flatrr\FlatArrayTrait;

class User implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $uuid, $name;
    protected $created, $created_by;
    protected $updated, $updated_by;
    protected $updated_last;
    protected $groups;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid('usr');
        $this->name = @$metadata['name'] ?? Users::randomName();
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::uuid();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'] ?? Session::uuid();
        $this->rawSet(null, $data);
    }

    /**
     * Get all the groups to which this user belongs
     *
     * @return Group[]
     */
    public function groups(): array
    {
        if ($this->groups === null) {
            $this->groups = [new Group('users', 'All users')];
            $this->groups = array_merge($this->groups, Users::groups($this->uuid()));
        }
        return $this->groups;
    }

    public function __toString()
    {
        $a = (new A)
            ->addClass('user-link')
            ->addChild($this->name());
        $url = $this->profile();
        if (Permissions::url($url)) {
            $a
                ->setAttribute('href', $url)
                ->setAttribute('target', '_top');
        }
        return $a->__toString();
    }

    /**
     * Add an email address to account. Immediately sends notification emails
     * and updates record in database.
     *
     * @param string $email
     * @param string $comment
     * @param boolean $skipVerification
     * @return void
     */
    public function addEmail(string $email, string $comment = '', bool $skipVerification = false)
    {
        $email = strtolower($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email address");
        }
        $value = [
            'address' => $email,
            'time' => time(),
            'comment' => $comment
        ];
        foreach ($this['emails'] ?? [] as $k => $existing) {
            if ($existing[0] == $email) {
                unset($this['emails.' . $k]);
                $this['emails.' . $k] = $value;
                return;
            }
        }
        $this->push('emails', $value);
        if ($skipVerification && count($this->emails()) == 1) $this->setPrimaryEmail($email);
        if (!$skipVerification) $this->sendVerificationEmail($email);
        $this->update();
        // send notification to all verified email addresses
        // only sends if we skipped verification and this is at least the second address on account
        if ($skipVerification && count($this->emails()) > 1) {
            $messages = Email::newForUser_all(
                'service',
                $this,
                'Email address added to account',
                new RichContent(Templates::render(
                    '/email/account/email-added.php',
                    [
                        'user' => $this,
                        'email' => $email
                    ]
                ))
            );
            foreach ($messages as $message) {
                Emails::send($message);
            }
        }
    }

    /**
     * Set primary email to the given address. Will only have an effect if
     * called with an email that is configured and verified for this account.
     * 
     * Immediately sends notification emails and updates record in database.
     *
     * @param string $email
     * @return void
     */
    public function setPrimaryEmail(string $email)
    {
        $alreadyHadPrimary = !!$this->primaryEmail();
        $email = strtolower($email);
        $updated = false;
        foreach ($this['emails'] ?? [] as $i => $row) {
            if ($row['address'] == $email && !@$row['verification'] && !@$row['primary']) $updated = true;
            $this["emails.$i.primary"] = $row['address'] == $email;
        }
        if (!$updated) return;
        $this->update();
        // send notification to all verified email addresses, if there was
        // already a different primary email address
        if ($alreadyHadPrimary) {
            $messages = Email::newForUser_all(
                'service',
                $this,
                'Primary email address changed',
                new RichContent(Templates::render(
                    '/email/account/primary-email-changed.php',
                    [
                        'user' => $this,
                        'email' => $email
                    ]
                ))
            );
            foreach ($messages as $message) {
                Emails::send($message);
            }
        }
    }

    /**
     * Mark an email as verified. Immediately sends notification email and 
     * updates the record in the database.
     *
     * @param string $email
     * @return void
     */
    public function verifyEmail(string $email)
    {
        $email = strtolower($email);
        foreach ($this['emails'] ?? [] as $i => $row) {
            if ($row['address'] == $email) {
                unset($this["emails.$i.verification"]);
                if (count($this->emails()) == 1) $this->setPrimaryEmail($email);
                $this->update();
                // send notification emails
                $messages = Email::newForUser_all(
                    'service',
                    $this,
                    'Email address added to account',
                    new RichContent(Templates::render(
                        '/email/account/email-added.php',
                        [
                            'user' => $this,
                            'email' => $email
                        ]
                    ))
                );
                foreach ($messages as $message) {
                    Emails::send($message);
                }
            }
        }
    }

    /**
     * Generate a fresh token and send a verification email to the given email.
     *
     * @param string $email
     * @return void
     */
    public function sendVerificationEmail(string $email)
    {
        $email = strtolower($email);
        $i = null;
        foreach ($this['emails'] ?? [] as $i => $row) {
            if ($row['address'] == $email) break;
        }
        if ($i === null) return;
        $this['emails.' . $i . '.verification'] = [
            'time' => time(),
            'token' => $token = Digraph::uuid()
        ];
        $email = Email::newForEmail(
            'service',
            $email,
            'Verify your email address',
            new RichContent(Templates::render(
                '/email/account/email-verification.php',
                [
                    'user' => $this,
                    'email' => $email,
                    'link' => new URL('/~verify_email/?token=' . $token . '&user=' . $this->uuid())
                ]
            ))
        );
        Emails::send($email);
        $this->update();
    }

    /**
     * Remove an email from this account. Only does anything if email is in
     * account. Immediately sends notification emails and updates record in
     * database.
     *
     * @param string $email
     * @return void
     */
    public function removeEmail(string $email)
    {
        // send notification to all emails, including the one removed
        // note that newForUser_all uses the emails() method, so it will only
        // send to verified emails
        if (in_array($email, $this->emails())) {
            $messages = Email::newForUser_all(
                'service',
                $this,
                'Email address removed from account',
                new RichContent(Templates::render(
                    '/email/account/email-removed.php',
                    [
                        'user' => $this,
                        'email' => $email
                    ]
                ))
            );
            foreach ($messages as $message) {
                Emails::send($message);
            }
        }
        // remove email from array
        $emails = array_filter(
            $this['emails'],
            function ($e) use ($email) {
                return $email != $e[0];
            }
        );
        unset($this['emails']);
        $this['emails'] = $emails;
    }

    /**
     * Return a list of all verified emails attached to this account.
     *
     * @return array
     */
    public function emails(): array
    {
        return array_map(
            function ($e) {
                return $e['address'];
            },
            array_filter(
                $this['emails'] ?? [],
                function (array $email) {
                    return !@$email['verification'];
                }
            )
        );
    }

    /**
     * Get the primary email address for account, if it exists
     *
     * @return string|null
     */
    public function primaryEmail(): ?string
    {
        foreach ($this['emails'] as $row) {
            if (@$row['primary'] && !@$row['verification']) {
                return $row['address'];
            }
        }
        return null;
    }

    public function name(string $name = null): string
    {
        if ($name) {
            $this->name = $name;
        }
        return $this->name;
    }

    public function profile(): URL
    {
        return new URL('/~user/?user=' . $this->uuid());
    }

    public function insert()
    {
        return Users::insert($this);
    }

    public function update()
    {
        return Users::update($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
    }

    public function createdByUUID(): string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }

    public function updatedLast(): DateTime
    {
        return clone $this->updated;
    }
}
