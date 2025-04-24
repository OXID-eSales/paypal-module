/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { CoreSettings } from './CoreSettings'; // Ensure these paths are correct
import { CountryList } from './CountryList';
import { Manufacturers } from './Manufacturers';
import { AdminPanel } from './AdminPanel';
import { ProductCategories } from './ProductCategories';
import { ModulesList } from './ModulesList';
import { Orders } from './Orders';
import { Products } from './Products';
import { Users } from './Users';
import { Languages } from './Languages';
import { DiagnosticsTool } from './DiagnosticsTool';
import { Tools } from './Tools';
import { SystemInfo } from './SystemInfo';
import { SystemHealth } from './SystemHealth';
import { CMSPages } from './CMSPages';
import { Newsletter } from './Newsletter';
import { GenericImport } from './GenericImport';
import { GenericExport } from './GenericExport';
import { Vouchers } from './Vouchers';

export async function openCoreSettings(page) {
    await page.locator('text=mxmainmenu').click();
    await page.locator('text=mxcoresett').click();
    await page.locator('iframe').waitForLoadState();
    return new CoreSettings(page);
}

export async function openCountries(page) {
    await page.locator('text=mxmainmenu').click();
    await page.locator('text=mxcountries').click();
    await page.locator('iframe').waitForLoadState();
    return new CountryList(page);
}

export async function openManufacturers(page) {
    await page.locator('text=mxmainmenu').click();
    await page.locator('text=mxmanufacturer').click();
    await page.locator('iframe').waitForLoadState();
    return new Manufacturers(page);
}

export async function openHomePage(page) {
    await page.locator('text=NAVIGATION_HOME').click();
    await page.locator('iframe').waitForLoadState();
    return new AdminPanel(page);
}

export async function openCategories(page) {
    await page.locator('text=mxmanageprod').click();
    await page.locator('text=mxcategories').click();
    await page.locator('iframe').waitForLoadState();
    return new ProductCategories(page);
}

export async function openModules(page) {
    await page.locator('text=mxextensions').click();
    await page.locator('text=mxmodule').click();
    await page.locator('iframe').waitForLoadState();
    return new ModulesList(page);
}

export async function openOrders(page) {
    await page.locator('text=mxorders').click();
    await page.locator('text=mxdisplayorders').click();
    await page.locator('iframe').waitForLoadState();
    return new Orders(page);
}

export async function openProducts(page) {
    await page.locator('text=mxmanageprod').click();
    await page.locator('text=mxarticles').click();
    await page.locator('iframe').waitForLoadState();
    return new Products(page);
}

export async function openUsers(page) {
    await page.locator('text=mxuadmin').click();
    await page.locator('text=mxusers').click();
    await page.locator('iframe').waitForLoadState();
    return new Users(page);
}

export async function openLanguages(page) {
    await page.locator('text=mxmainmenu').click();
    await page.locator('text=mxlanguages').click();
    await page.locator('iframe').waitForLoadState();
    return new Languages(page);
}

export async function openDiagnosticsTool(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=oxdiag_menu').click();
    await page.locator('iframe').waitForLoadState();
    return new DiagnosticsTool(page);
}

export async function openTools(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=mxtools').click();
    await page.locator('iframe').waitForLoadState();
    return new Tools(page);
}

export async function openSystemInfo(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=mxsysinfo').click();
    await page.locator('iframe').waitForLoadState();
    return new SystemInfo(page);
}

export async function openSystemHealth(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=mxsysreq').click();
    await page.locator('iframe').waitForLoadState();
    return new SystemHealth(page);
}

export async function openCMSPages(page) {
    await page.locator('text=mxcustnews').click();
    await page.locator('text=mxcontent').click();
    await page.locator('iframe').waitForLoadState();
    return new CMSPages(page);
}

export async function openNewsletter(page) {
    await page.locator('text=mxcustnews').click();
    await page.locator('text=mxnewsletter').click();
    await page.locator('iframe').waitForLoadState();
    return new Newsletter(page);
}

export async function openGenericImport(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=mxgenimp').click();
    await page.locator('iframe').waitForLoadState();
    return new GenericImport(page);
}

export async function openGenericExport(page) {
    await page.locator('text=mxservice').click();
    await page.locator('text=mxgenexp').click();
    await page.locator('iframe').waitForLoadState();
    return new GenericExport(page);
}

export async function openVouchers(page) {
    await page.locator('text=mxshopsett').click();
    await page.locator('text=mxvouchers').click();
    await page.locator('iframe').waitForLoadState();
    return new Vouchers(page);
}
