import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file

export class Newsletter extends Page {
    async exportReciepents() {
        const { user } = this;

        await user.click(await Translator.translate('tbclnewsletter_recipients'));

        return this;
    }
}
