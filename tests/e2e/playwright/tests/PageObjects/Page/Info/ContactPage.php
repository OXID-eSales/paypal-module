<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Info;

use OxidEsales\Codeception\Page\DataObject\ContactData;
use OxidEsales\Codeception\Page\Page;

class ContactPage extends Page
{
    private string $salutation = '//select[@name="editval[oxuser__oxsal]"]';
    private string $firstName = '//input[@name="editval[oxuser__oxfname]"]';
    private string $lastName = '//input[@name="editval[oxuser__oxlname]"]';
    private string $email = '//input[@name="editval[oxuser__oxusername]"]';
    private string $subject = '//input[@name="c_subject"]';
    private string $message = '//textarea[@name="c_message"]';
    private string $sendButton = '//button[@class="btn btn-highlight" and contains(text(), "Send")]';

    public function fillInContactData(ContactData $contactData): static
    {
        $I = $this->user;
        $I->selectOption($this->salutation, $contactData->getSalutation());
        $I->fillField($this->firstName, $contactData->getFirstName());
        $I->fillField($this->lastName, $contactData->getLastName());
        $I->fillField($this->email, $contactData->getEmail());
        $I->fillField($this->subject, $contactData->getSubject());
        $I->fillField($this->message, $contactData->getMessage());
        $I->waitForPageLoad();

        return $this;
    }

    public function sendContactData(): static
    {
        $I = $this->user;
        $I->retryClick($this->sendButton);
        $I->waitForPageLoad();

        return $this;
    }
}
