import { Page } from './Page'; // Assuming Page is in a separate file
import { OrderList } from './OrderList'; // Assuming OrderList is in a separate file

export class DownloadsOrderPage extends Page {
    productNumberInDownloadsTab = "//tr[@id='file.1']/td[1]";
    titleInDownloadsTab = "//tr[@id='file.1']/td[2]";
    downloadableFileInDownloadsTab = "//tr[@id='file.1']/td[3]";
    firstDownloadInDownloadsTab = "//tr[@id='file.1']/td[4]";
    lastDownloadInDownloadsTab = "//tr[@id='file.1']/td[5]";
    countInDownloadsTab = "//tr[@id='file.1']/td[6]";
    maxCountInDownloadsTab = "//tr[@id='file.1']/td[7]";
}
