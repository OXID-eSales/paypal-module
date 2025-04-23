import { Page } from './Page'; // Assuming Page is in a separate file
import { OrderList } from './OrderList'; // Assuming OrderList is in a separate file

export class AddressesOrderPage extends Page {
    firstNameInAddressesTab = "//input[@name='editval[oxorder__oxbillfname]']";
    lastNameInAddressesTab = "//input[@name='editval[oxorder__oxbilllname]']";
    loginNameInAddressesTab = "//input[@name='editval[oxorder__oxbillemail]']";
    zipCodeInAddressesTab = "//input[@name='editval[oxorder__oxbillzip]']";
    cityInAddressesTab = "//input[@name='editval[oxorder__oxbillcity]']";
}
