export class Manufacturer {
    title = '';
    shortDescription = '';
    sortValue = 0;
    active = false;
    icon = '';

    async getIcon() {
        return this.icon;
    }

    async setIcon(icon) {
        this.icon = icon;
    }

    async getTitle() {
        return this.title;
    }

    async setTitle(title) {
        this.title = title;
    }

    async getShortDescription() {
        return this.shortDescription;
    }

    async setShortDescription(shortDescription) {
        this.shortDescription = shortDescription;
    }

    async getSortValue() {
        return this.sortValue;
    }

    async setSortValue(sortValue) {
        this.sortValue = sortValue;
    }

    async isActive() {
        return this.active;
    }

    async setActive(active) {
        this.active = active;
    }
}
