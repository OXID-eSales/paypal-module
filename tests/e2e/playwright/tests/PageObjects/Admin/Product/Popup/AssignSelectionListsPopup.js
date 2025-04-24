import { WebDriverKeys } from 'playwright'; // Assuming you are using Playwright or a similar library
import { Page } from './Page'; // Assuming Page is in a separate file

export class AssignSelectionListsPopup extends Page {
    unassignedList = '#container1';
    assignedList = '#container2';
    titleFilter = 'input[name="_0"]';
    firstRow = '.yui-dt-data tr.yui-dt-first';

    async assignSelectionByTitle(itemTitle) {
        const { user } = this;

        await user.fillField(`${this.unassignedList} ${this.titleFilter}`, itemTitle);
        await user.pressKey(`${this.unassignedList} ${this.titleFilter}`, WebDriverKeys.Enter);
        await user.waitForTimeout(3000);
        await user.retryDragAndDrop(`${this.unassignedList} ${this.firstRow}`, this.assignedList);
        await user.waitForTimeout(3000);

        return this;
    }
}
