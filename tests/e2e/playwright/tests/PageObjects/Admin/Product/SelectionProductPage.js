import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file
import { AssignSelectionListsPopup } from './Popup/AssignSelectionListsPopup'; // Assuming AssignSelectionListsPopup is in a separate file

export class SelectionProductPage extends Page {
    assignSelectionListButton = 'input.edittext[type="button"][value="%s"]';
    assignSelectionListButtonValue = 'ARTICLE_ATTRIBUTE_ASSIGNSELECTLIST';
    unassignedSelectionsListTitle = 'ARTICLE_ATTRIBUTE_NOSELLIST';
    unassignedList = '#container1';
    assignedList = '#container2';

    async openAssignSelectionListPopup() {
        const { user } = this;

        const assignSelectionListButtonSelector = this.assignSelectionListButton.replace(
            '%s',
            await Translator.translate(this.assignSelectionListButtonValue)
        );

        await user.click(assignSelectionListButtonSelector);
        await user.waitForDocumentReadyState();
        await user.switchToNextTab();
        await user.waitForDocumentReadyState();
        await user.maximizeWindow();

        await user.waitForText(await Translator.translate(this.unassignedSelectionsListTitle));
        await user.waitForElement(this.unassignedList);
        await user.waitForElement(this.assignedList);

        return new AssignSelectionListsPopup(user);
    }
}
