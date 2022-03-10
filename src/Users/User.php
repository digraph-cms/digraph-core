<?php

namespace DigraphCMS\Users;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Email\Email;
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
    protected $groups;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->name = @$metadata['name'] ?? Users::randomName();
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::user();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'] ?? Session::user();
        $this->rawSet(null, $data);
        $this->changed = false;
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
    }

    public function setPrimaryEmail(string $email)
    {
        $email = strtolower($email);
        foreach ($this['emails'] ?? [] as $i => $row) {
            $this["emails.$i.primary"] = $row['address'] == $email;
        }
    }

    public function verifyEmail(string $email)
    {
        $email = strtolower($email);
        foreach ($this['emails'] ?? [] as $i => $row) {
            if ($row['address'] == $email) {
                unset($this["emails.$i.verification"]);
                if (count($this->emails()) == 1) $this->setPrimaryEmail($email);
            }
        }
    }

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
            'token' => $token = Digraph::uuid(true)
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
                    'link' => new URL('/~verify-email/?token=' . $token . '&user=' . $this->uuid())
                ]
            ))
        );
        $email->send();
        $this->update();
    }

    public function removeEmail(string $email)
    {
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
        return new URL('/~users/' . $this->uuid() . '.html');
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
        return $this->created_by ? Users::user($this->created_by) : Users::guest();
    }

    public function updatedBy(): User
    {
        return $this->updated_by ? Users::user($this->updated_by) : Users::guest();
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): ?string
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
