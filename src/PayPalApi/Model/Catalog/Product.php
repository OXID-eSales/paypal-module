<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Catalog;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The product details.
 *
 * generated from: product.json
 */
class Product implements JsonSerializable
{
    use BaseModel;

    /** Physical goods */
    public const TYPE_PHYSICAL = 'PHYSICAL';

    /** For digital goods, the value must be set to DIGITAL to get the best rates. For more details, please contact your account manager. */
    public const TYPE_DIGITAL = 'DIGITAL';

    /** Product representing a service. Example: Tech Support */
    public const TYPE_SERVICE = 'SERVICE';

    /** A/C, Refrigeration Repair */
    public const CATEGORY_AC_REFRIGERATION_REPAIR = 'AC_REFRIGERATION_REPAIR';

    /** Academic Software */
    public const CATEGORY_ACADEMIC_SOFTWARE = 'ACADEMIC_SOFTWARE';

    /** Accessories */
    public const CATEGORY_ACCESSORIES = 'ACCESSORIES';

    /** Accounting */
    public const CATEGORY_ACCOUNTING = 'ACCOUNTING';

    /** Adult */
    public const CATEGORY_ADULT = 'ADULT';

    /** Advertising */
    public const CATEGORY_ADVERTISING = 'ADVERTISING';

    /** Affiliated Auto Rental */
    public const CATEGORY_AFFILIATED_AUTO_RENTAL = 'AFFILIATED_AUTO_RENTAL';

    /** Agencies */
    public const CATEGORY_AGENCIES = 'AGENCIES';

    /** Aggregators */
    public const CATEGORY_AGGREGATORS = 'AGGREGATORS';

    /** Agricultural Cooperative for Mail Order */
    public const CATEGORY_AGRICULTURAL_COOPERATIVE_FOR_MAIL_ORDER = 'AGRICULTURAL_COOPERATIVE_FOR_MAIL_ORDER';

    /** Air Carriers, Airlines */
    public const CATEGORY_AIR_CARRIERS_AIRLINES = 'AIR_CARRIERS_AIRLINES';

    /** Airlines */
    public const CATEGORY_AIRLINES = 'AIRLINES';

    /** Airports, Flying Fields */
    public const CATEGORY_AIRPORTS_FLYING_FIELDS = 'AIRPORTS_FLYING_FIELDS';

    /** Alcoholic Beverages */
    public const CATEGORY_ALCOHOLIC_BEVERAGES = 'ALCOHOLIC_BEVERAGES';

    /** Amusement Parks/Carnivals */
    public const CATEGORY_AMUSEMENT_PARKS_CARNIVALS = 'AMUSEMENT_PARKS_CARNIVALS';

    /** Animation */
    public const CATEGORY_ANIMATION = 'ANIMATION';

    /** Antiques */
    public const CATEGORY_ANTIQUES = 'ANTIQUES';

    /** Appliances */
    public const CATEGORY_APPLIANCES = 'APPLIANCES';

    /** Aquariams Seaquariums Dolphinariums */
    public const CATEGORY_AQUARIAMS_SEAQUARIUMS_DOLPHINARIUMS = 'AQUARIAMS_SEAQUARIUMS_DOLPHINARIUMS';

    /** Architectural,Engineering,And Surveying Services */
    public const CATEGORY_ARCHITECTURAL_ENGINEERING_AND_SURVEYING_SERVICES = 'ARCHITECTURAL_ENGINEERING_AND_SURVEYING_SERVICES';

    /** Art & Craft Supplies */
    public const CATEGORY_ART_AND_CRAFT_SUPPLIES = 'ART_AND_CRAFT_SUPPLIES';

    /** Art dealers and galleries */
    public const CATEGORY_ART_DEALERS_AND_GALLERIES = 'ART_DEALERS_AND_GALLERIES';

    /** Artifacts, Grave related, and Native American Crafts */
    public const CATEGORY_ARTIFACTS_GRAVE_RELATED_AND_NATIVE_AMERICAN_CRAFTS = 'ARTIFACTS_GRAVE_RELATED_AND_NATIVE_AMERICAN_CRAFTS';

    /** Arts and crafts */
    public const CATEGORY_ARTS_AND_CRAFTS = 'ARTS_AND_CRAFTS';

    /** Arts, crafts, and collectibles */
    public const CATEGORY_ARTS_CRAFTS_AND_COLLECTIBLES = 'ARTS_CRAFTS_AND_COLLECTIBLES';

    /** Audio books */
    public const CATEGORY_AUDIO_BOOKS = 'AUDIO_BOOKS';

    /** Auto Associations/Clubs */
    public const CATEGORY_AUTO_ASSOCIATIONS_CLUBS = 'AUTO_ASSOCIATIONS_CLUBS';

    /** Auto dealer - used only */
    public const CATEGORY_AUTO_DEALER_USED_ONLY = 'AUTO_DEALER_USED_ONLY';

    /** Auto Rentals */
    public const CATEGORY_AUTO_RENTALS = 'AUTO_RENTALS';

    /** Auto service */
    public const CATEGORY_AUTO_SERVICE = 'AUTO_SERVICE';

    /** Automated Fuel Dispensers */
    public const CATEGORY_AUTOMATED_FUEL_DISPENSERS = 'AUTOMATED_FUEL_DISPENSERS';

    /** Automobile Associations */
    public const CATEGORY_AUTOMOBILE_ASSOCIATIONS = 'AUTOMOBILE_ASSOCIATIONS';

    /** Automotive */
    public const CATEGORY_AUTOMOTIVE = 'AUTOMOTIVE';

    /** Automotive Repair Shops - Non-Dealer */
    public const CATEGORY_AUTOMOTIVE_REPAIR_SHOPS_NON_DEALER = 'AUTOMOTIVE_REPAIR_SHOPS_NON_DEALER';

    /** Automotive Top And Body Shops */
    public const CATEGORY_AUTOMOTIVE_TOP_AND_BODY_SHOPS = 'AUTOMOTIVE_TOP_AND_BODY_SHOPS';

    /** Aviation */
    public const CATEGORY_AVIATION = 'AVIATION';

    /** Babies Clothing & Supplies */
    public const CATEGORY_BABIES_CLOTHING_AND_SUPPLIES = 'BABIES_CLOTHING_AND_SUPPLIES';

    /** Baby */
    public const CATEGORY_BABY = 'BABY';

    /** Bands,Orchestras,Entertainers */
    public const CATEGORY_BANDS_ORCHESTRAS_ENTERTAINERS = 'BANDS_ORCHESTRAS_ENTERTAINERS';

    /** Barbies */
    public const CATEGORY_BARBIES = 'BARBIES';

    /** Bath and body */
    public const CATEGORY_BATH_AND_BODY = 'BATH_AND_BODY';

    /** Batteries */
    public const CATEGORY_BATTERIES = 'BATTERIES';

    /** Bean Babies */
    public const CATEGORY_BEAN_BABIES = 'BEAN_BABIES';

    /** Beauty */
    public const CATEGORY_BEAUTY = 'BEAUTY';

    /** Beauty and fragrances */
    public const CATEGORY_BEAUTY_AND_FRAGRANCES = 'BEAUTY_AND_FRAGRANCES';

    /** Bed & Bath */
    public const CATEGORY_BED_AND_BATH = 'BED_AND_BATH';

    /** Bicycle Shops-Sales And Service */
    public const CATEGORY_BICYCLE_SHOPS_SALES_AND_SERVICE = 'BICYCLE_SHOPS_SALES_AND_SERVICE';

    /** Bicycles & Accessories */
    public const CATEGORY_BICYCLES_AND_ACCESSORIES = 'BICYCLES_AND_ACCESSORIES';

    /** Billiard/Pool Establishments */
    public const CATEGORY_BILLIARD_POOL_ESTABLISHMENTS = 'BILLIARD_POOL_ESTABLISHMENTS';

    /** Boat Dealers */
    public const CATEGORY_BOAT_DEALERS = 'BOAT_DEALERS';

    /** Boat Rentals And Leasing */
    public const CATEGORY_BOAT_RENTALS_AND_LEASING = 'BOAT_RENTALS_AND_LEASING';

    /** Boating, sailing and accessories */
    public const CATEGORY_BOATING_SAILING_AND_ACCESSORIES = 'BOATING_SAILING_AND_ACCESSORIES';

    /** Books */
    public const CATEGORY_BOOKS = 'BOOKS';

    /** Books and magazines */
    public const CATEGORY_BOOKS_AND_MAGAZINES = 'BOOKS_AND_MAGAZINES';

    /** Books, Manuscripts */
    public const CATEGORY_BOOKS_MANUSCRIPTS = 'BOOKS_MANUSCRIPTS';

    /** Books, Periodicals And Newspapers */
    public const CATEGORY_BOOKS_PERIODICALS_AND_NEWSPAPERS = 'BOOKS_PERIODICALS_AND_NEWSPAPERS';

    /** Bowling Alleys */
    public const CATEGORY_BOWLING_ALLEYS = 'BOWLING_ALLEYS';

    /** Bulletin board */
    public const CATEGORY_BULLETIN_BOARD = 'BULLETIN_BOARD';

    /** Bus line */
    public const CATEGORY_BUS_LINE = 'BUS_LINE';

    /** Bus Lines,Charters,Tour Buses */
    public const CATEGORY_BUS_LINES_CHARTERS_TOUR_BUSES = 'BUS_LINES_CHARTERS_TOUR_BUSES';

    /** Business */
    public const CATEGORY_BUSINESS = 'BUSINESS';

    /** Business and secretarial schools */
    public const CATEGORY_BUSINESS_AND_SECRETARIAL_SCHOOLS = 'BUSINESS_AND_SECRETARIAL_SCHOOLS';

    /** Buying And Shopping Services And Clubs */
    public const CATEGORY_BUYING_AND_SHOPPING_SERVICES_AND_CLUBS = 'BUYING_AND_SHOPPING_SERVICES_AND_CLUBS';

    /** Cable,Satellite,And Other Pay Television And Radio Services */
    public const CATEGORY_CABLE_SATELLITE_AND_OTHER_PAY_TELEVISION_AND_RADIO_SERVICES = 'CABLE_SATELLITE_AND_OTHER_PAY_TELEVISION_AND_RADIO_SERVICES';

    /** Cable, satellite, and other pay TV and radio */
    public const CATEGORY_CABLE_SATELLITE_AND_OTHER_PAY_TV_AND_RADIO = 'CABLE_SATELLITE_AND_OTHER_PAY_TV_AND_RADIO';

    /** Camera and photographic supplies */
    public const CATEGORY_CAMERA_AND_PHOTOGRAPHIC_SUPPLIES = 'CAMERA_AND_PHOTOGRAPHIC_SUPPLIES';

    /** Cameras */
    public const CATEGORY_CAMERAS = 'CAMERAS';

    /** Cameras & Photography */
    public const CATEGORY_CAMERAS_AND_PHOTOGRAPHY = 'CAMERAS_AND_PHOTOGRAPHY';

    /** Camper,Recreational And Utility Trailer Dealers */
    public const CATEGORY_CAMPER_RECREATIONAL_AND_UTILITY_TRAILER_DEALERS = 'CAMPER_RECREATIONAL_AND_UTILITY_TRAILER_DEALERS';

    /** Camping and outdoors */
    public const CATEGORY_CAMPING_AND_OUTDOORS = 'CAMPING_AND_OUTDOORS';

    /** Camping & Survival */
    public const CATEGORY_CAMPING_AND_SURVIVAL = 'CAMPING_AND_SURVIVAL';

    /** Car And Truck Dealers */
    public const CATEGORY_CAR_AND_TRUCK_DEALERS = 'CAR_AND_TRUCK_DEALERS';

    /** Car And Truck Dealers - Used Only */
    public const CATEGORY_CAR_AND_TRUCK_DEALERS_USED_ONLY = 'CAR_AND_TRUCK_DEALERS_USED_ONLY';

    /** Car Audio & Electronics */
    public const CATEGORY_CAR_AUDIO_AND_ELECTRONICS = 'CAR_AUDIO_AND_ELECTRONICS';

    /** Car rental agency */
    public const CATEGORY_CAR_RENTAL_AGENCY = 'CAR_RENTAL_AGENCY';

    /** Catalog Merchant */
    public const CATEGORY_CATALOG_MERCHANT = 'CATALOG_MERCHANT';

    /** Catalog/Retail Merchant */
    public const CATEGORY_CATALOG_RETAIL_MERCHANT = 'CATALOG_RETAIL_MERCHANT';

    /** Catering services */
    public const CATEGORY_CATERING_SERVICES = 'CATERING_SERVICES';

    /** Charity */
    public const CATEGORY_CHARITY = 'CHARITY';

    /** Check Cashier */
    public const CATEGORY_CHECK_CASHIER = 'CHECK_CASHIER';

    /** Child Care Services */
    public const CATEGORY_CHILD_CARE_SERVICES = 'CHILD_CARE_SERVICES';

    /** Children Books */
    public const CATEGORY_CHILDREN_BOOKS = 'CHILDREN_BOOKS';

    /** Chiropodists/Podiatrists */
    public const CATEGORY_CHIROPODISTS_PODIATRISTS = 'CHIROPODISTS_PODIATRISTS';

    /** Chiropractors */
    public const CATEGORY_CHIROPRACTORS = 'CHIROPRACTORS';

    /** Cigar Stores And Stands */
    public const CATEGORY_CIGAR_STORES_AND_STANDS = 'CIGAR_STORES_AND_STANDS';

    /** Civic, Social, Fraternal Associations */
    public const CATEGORY_CIVIC_SOCIAL_FRATERNAL_ASSOCIATIONS = 'CIVIC_SOCIAL_FRATERNAL_ASSOCIATIONS';

    /** Civil/Social/Frat Associations */
    public const CATEGORY_CIVIL_SOCIAL_FRAT_ASSOCIATIONS = 'CIVIL_SOCIAL_FRAT_ASSOCIATIONS';

    /** Clothing */
    public const CATEGORY_CLOTHING = 'CLOTHING';

    /** Clothing, accessories, and shoes */
    public const CATEGORY_CLOTHING_ACCESSORIES_AND_SHOES = 'CLOTHING_ACCESSORIES_AND_SHOES';

    /** Clothing Rental */
    public const CATEGORY_CLOTHING_RENTAL = 'CLOTHING_RENTAL';

    /** Coffee and tea */
    public const CATEGORY_COFFEE_AND_TEA = 'COFFEE_AND_TEA';

    /** Coin Operated Banks & Casinos */
    public const CATEGORY_COIN_OPERATED_BANKS_AND_CASINOS = 'COIN_OPERATED_BANKS_AND_CASINOS';

    /** Collectibles */
    public const CATEGORY_COLLECTIBLES = 'COLLECTIBLES';

    /** Collection agency */
    public const CATEGORY_COLLECTION_AGENCY = 'COLLECTION_AGENCY';

    /** Colleges and universities */
    public const CATEGORY_COLLEGES_AND_UNIVERSITIES = 'COLLEGES_AND_UNIVERSITIES';

    /** Commercial Equipment */
    public const CATEGORY_COMMERCIAL_EQUIPMENT = 'COMMERCIAL_EQUIPMENT';

    /** Commercial Footwear */
    public const CATEGORY_COMMERCIAL_FOOTWEAR = 'COMMERCIAL_FOOTWEAR';

    /** Commercial photography */
    public const CATEGORY_COMMERCIAL_PHOTOGRAPHY = 'COMMERCIAL_PHOTOGRAPHY';

    /** Commercial photography, art, and graphics */
    public const CATEGORY_COMMERCIAL_PHOTOGRAPHY_ART_AND_GRAPHICS = 'COMMERCIAL_PHOTOGRAPHY_ART_AND_GRAPHICS';

    /** Commercial Sports/Professiona */
    public const CATEGORY_COMMERCIAL_SPORTS_PROFESSIONA = 'COMMERCIAL_SPORTS_PROFESSIONA';

    /** Commodities and futures exchange */
    public const CATEGORY_COMMODITIES_AND_FUTURES_EXCHANGE = 'COMMODITIES_AND_FUTURES_EXCHANGE';

    /** Computer and data processing services */
    public const CATEGORY_COMPUTER_AND_DATA_PROCESSING_SERVICES = 'COMPUTER_AND_DATA_PROCESSING_SERVICES';

    /** Computer Hardware & Software */
    public const CATEGORY_COMPUTER_HARDWARE_AND_SOFTWARE = 'COMPUTER_HARDWARE_AND_SOFTWARE';

    /** Computer Maintenance, Repair And Services Not Elsewhere Clas */
    public const CATEGORY_COMPUTER_MAINTENANCE_REPAIR_AND_SERVICES_NOT_ELSEWHERE_CLAS = 'COMPUTER_MAINTENANCE_REPAIR_AND_SERVICES_NOT_ELSEWHERE_CLAS';

    /** Construction */
    public const CATEGORY_CONSTRUCTION = 'CONSTRUCTION';

    /** Construction Materials Not Elsewhere Classified */
    public const CATEGORY_CONSTRUCTION_MATERIALS_NOT_ELSEWHERE_CLASSIFIED = 'CONSTRUCTION_MATERIALS_NOT_ELSEWHERE_CLASSIFIED';

    /** Consulting services */
    public const CATEGORY_CONSULTING_SERVICES = 'CONSULTING_SERVICES';

    /** Consumer Credit Reporting Agencies */
    public const CATEGORY_CONSUMER_CREDIT_REPORTING_AGENCIES = 'CONSUMER_CREDIT_REPORTING_AGENCIES';

    /** Convalescent Homes */
    public const CATEGORY_CONVALESCENT_HOMES = 'CONVALESCENT_HOMES';

    /** Cosmetic Stores */
    public const CATEGORY_COSMETIC_STORES = 'COSMETIC_STORES';

    /** Counseling Services--Debt,Marriage,Personal */
    public const CATEGORY_COUNSELING_SERVICES_DEBT_MARRIAGE_PERSONAL = 'COUNSELING_SERVICES_DEBT_MARRIAGE_PERSONAL';

    /** Counterfeit Currency and Stamps */
    public const CATEGORY_COUNTERFEIT_CURRENCY_AND_STAMPS = 'COUNTERFEIT_CURRENCY_AND_STAMPS';

    /** Counterfeit Items */
    public const CATEGORY_COUNTERFEIT_ITEMS = 'COUNTERFEIT_ITEMS';

    /** Country Clubs */
    public const CATEGORY_COUNTRY_CLUBS = 'COUNTRY_CLUBS';

    /** Courier services */
    public const CATEGORY_COURIER_SERVICES = 'COURIER_SERVICES';

    /** Courier Services-Air And Ground,And Freight Forwarders */
    public const CATEGORY_COURIER_SERVICES_AIR_AND_GROUND_AND_FREIGHT_FORWARDERS = 'COURIER_SERVICES_AIR_AND_GROUND_AND_FREIGHT_FORWARDERS';

    /** Court Costs/Alimny/Child Supt */
    public const CATEGORY_COURT_COSTS_ALIMNY_CHILD_SUPT = 'COURT_COSTS_ALIMNY_CHILD_SUPT';

    /** Court Costs, Including Alimony and Child Support - Courts of Law */
    public const CATEGORY_COURT_COSTS_INCLUDING_ALIMONY_AND_CHILD_SUPPORT_COURTS_OF_LAW = 'COURT_COSTS_INCLUDING_ALIMONY_AND_CHILD_SUPPORT_COURTS_OF_LAW';

    /** Credit Cards */
    public const CATEGORY_CREDIT_CARDS = 'CREDIT_CARDS';

    /** Credit union */
    public const CATEGORY_CREDIT_UNION = 'CREDIT_UNION';

    /** Culture & Religion */
    public const CATEGORY_CULTURE_AND_RELIGION = 'CULTURE_AND_RELIGION';

    /** Dairy Products Stores */
    public const CATEGORY_DAIRY_PRODUCTS_STORES = 'DAIRY_PRODUCTS_STORES';

    /** Dance Halls,Studios,And Schools */
    public const CATEGORY_DANCE_HALLS_STUDIOS_AND_SCHOOLS = 'DANCE_HALLS_STUDIOS_AND_SCHOOLS';

    /** Decorative */
    public const CATEGORY_DECORATIVE = 'DECORATIVE';

    /** Dental */
    public const CATEGORY_DENTAL = 'DENTAL';

    /** Dentists And Orthodontists */
    public const CATEGORY_DENTISTS_AND_ORTHODONTISTS = 'DENTISTS_AND_ORTHODONTISTS';

    /** Department Stores */
    public const CATEGORY_DEPARTMENT_STORES = 'DEPARTMENT_STORES';

    /** Desktop PCs */
    public const CATEGORY_DESKTOP_PCS = 'DESKTOP_PCS';

    /** Devices */
    public const CATEGORY_DEVICES = 'DEVICES';

    /** Diecast, Toys Vehicles */
    public const CATEGORY_DIECAST_TOYS_VEHICLES = 'DIECAST_TOYS_VEHICLES';

    /** Digital games */
    public const CATEGORY_DIGITAL_GAMES = 'DIGITAL_GAMES';

    /** Digital media,books,movies,music */
    public const CATEGORY_DIGITAL_MEDIA_BOOKS_MOVIES_MUSIC = 'DIGITAL_MEDIA_BOOKS_MOVIES_MUSIC';

    /** Direct Marketing */
    public const CATEGORY_DIRECT_MARKETING = 'DIRECT_MARKETING';

    /** Direct Marketing - Catalog Merchant */
    public const CATEGORY_DIRECT_MARKETING_CATALOG_MERCHANT = 'DIRECT_MARKETING_CATALOG_MERCHANT';

    /** Direct Marketing - Inbound Tele */
    public const CATEGORY_DIRECT_MARKETING_INBOUND_TELE = 'DIRECT_MARKETING_INBOUND_TELE';

    /** Direct Marketing - Outbound Tele */
    public const CATEGORY_DIRECT_MARKETING_OUTBOUND_TELE = 'DIRECT_MARKETING_OUTBOUND_TELE';

    /** Direct Marketing - Subscription */
    public const CATEGORY_DIRECT_MARKETING_SUBSCRIPTION = 'DIRECT_MARKETING_SUBSCRIPTION';

    /** Discount Stores */
    public const CATEGORY_DISCOUNT_STORES = 'DISCOUNT_STORES';

    /** Door-To-Door Sales */
    public const CATEGORY_DOOR_TO_DOOR_SALES = 'DOOR_TO_DOOR_SALES';

    /** Drapery, window covering, and upholstery */
    public const CATEGORY_DRAPERY_WINDOW_COVERING_AND_UPHOLSTERY = 'DRAPERY_WINDOW_COVERING_AND_UPHOLSTERY';

    /** Drinking Places */
    public const CATEGORY_DRINKING_PLACES = 'DRINKING_PLACES';

    /** Drugstore */
    public const CATEGORY_DRUGSTORE = 'DRUGSTORE';

    /** Durable goods */
    public const CATEGORY_DURABLE_GOODS = 'DURABLE_GOODS';

    /** eCommerce Development */
    public const CATEGORY_ECOMMERCE_DEVELOPMENT = 'ECOMMERCE_DEVELOPMENT';

    /** eCommerce Services */
    public const CATEGORY_ECOMMERCE_SERVICES = 'ECOMMERCE_SERVICES';

    /** Educational and textbooks */
    public const CATEGORY_EDUCATIONAL_AND_TEXTBOOKS = 'EDUCATIONAL_AND_TEXTBOOKS';

    /** Electric Razor Stores */
    public const CATEGORY_ELECTRIC_RAZOR_STORES = 'ELECTRIC_RAZOR_STORES';

    /** Electrical and small appliance repair */
    public const CATEGORY_ELECTRICAL_AND_SMALL_APPLIANCE_REPAIR = 'ELECTRICAL_AND_SMALL_APPLIANCE_REPAIR';

    /** Electrical Contractors */
    public const CATEGORY_ELECTRICAL_CONTRACTORS = 'ELECTRICAL_CONTRACTORS';

    /** Electrical Parts and Equipment */
    public const CATEGORY_ELECTRICAL_PARTS_AND_EQUIPMENT = 'ELECTRICAL_PARTS_AND_EQUIPMENT';

    /** Electronic Cash */
    public const CATEGORY_ELECTRONIC_CASH = 'ELECTRONIC_CASH';

    /** Elementary and secondary schools */
    public const CATEGORY_ELEMENTARY_AND_SECONDARY_SCHOOLS = 'ELEMENTARY_AND_SECONDARY_SCHOOLS';

    /** Employment */
    public const CATEGORY_EMPLOYMENT = 'EMPLOYMENT';

    /** Entertainers */
    public const CATEGORY_ENTERTAINERS = 'ENTERTAINERS';

    /** Entertainment and media */
    public const CATEGORY_ENTERTAINMENT_AND_MEDIA = 'ENTERTAINMENT_AND_MEDIA';

    /** Equip, Tool, Furniture, And Appliance Rental And Leasing */
    public const CATEGORY_EQUIP_TOOL_FURNITURE_AND_APPLIANCE_RENTAL_AND_LEASING = 'EQUIP_TOOL_FURNITURE_AND_APPLIANCE_RENTAL_AND_LEASING';

    /** Escrow */
    public const CATEGORY_ESCROW = 'ESCROW';

    /** Event & Wedding Planning */
    public const CATEGORY_EVENT_AND_WEDDING_PLANNING = 'EVENT_AND_WEDDING_PLANNING';

    /** Exercise and fitness */
    public const CATEGORY_EXERCISE_AND_FITNESS = 'EXERCISE_AND_FITNESS';

    /** Exercise Equipment */
    public const CATEGORY_EXERCISE_EQUIPMENT = 'EXERCISE_EQUIPMENT';

    /** Exterminating and disinfecting services */
    public const CATEGORY_EXTERMINATING_AND_DISINFECTING_SERVICES = 'EXTERMINATING_AND_DISINFECTING_SERVICES';

    /** Fabrics & Sewing */
    public const CATEGORY_FABRICS_AND_SEWING = 'FABRICS_AND_SEWING';

    /** Family Clothing Stores */
    public const CATEGORY_FAMILY_CLOTHING_STORES = 'FAMILY_CLOTHING_STORES';

    /** Fashion jewelry */
    public const CATEGORY_FASHION_JEWELRY = 'FASHION_JEWELRY';

    /** Fast Food Restaurants */
    public const CATEGORY_FAST_FOOD_RESTAURANTS = 'FAST_FOOD_RESTAURANTS';

    /** Fiction and nonfiction */
    public const CATEGORY_FICTION_AND_NONFICTION = 'FICTION_AND_NONFICTION';

    /** Finance company */
    public const CATEGORY_FINANCE_COMPANY = 'FINANCE_COMPANY';

    /** Financial and investment advice */
    public const CATEGORY_FINANCIAL_AND_INVESTMENT_ADVICE = 'FINANCIAL_AND_INVESTMENT_ADVICE';

    /** Financial Institutions - Merchandise And Services */
    public const CATEGORY_FINANCIAL_INSTITUTIONS_MERCHANDISE_AND_SERVICES = 'FINANCIAL_INSTITUTIONS_MERCHANDISE_AND_SERVICES';

    /** Firearm accessories */
    public const CATEGORY_FIREARM_ACCESSORIES = 'FIREARM_ACCESSORIES';

    /** Firearms, Weapons and Knives */
    public const CATEGORY_FIREARMS_WEAPONS_AND_KNIVES = 'FIREARMS_WEAPONS_AND_KNIVES';

    /** Fireplace, and fireplace screens */
    public const CATEGORY_FIREPLACE_AND_FIREPLACE_SCREENS = 'FIREPLACE_AND_FIREPLACE_SCREENS';

    /** Fireworks */
    public const CATEGORY_FIREWORKS = 'FIREWORKS';

    /** Fishing */
    public const CATEGORY_FISHING = 'FISHING';

    /** Florists */
    public const CATEGORY_FLORISTS = 'FLORISTS';

    /** Flowers */
    public const CATEGORY_FLOWERS = 'FLOWERS';

    /** Food, Drink & Nutrition */
    public const CATEGORY_FOOD_DRINK_AND_NUTRITION = 'FOOD_DRINK_AND_NUTRITION';

    /** Food Products */
    public const CATEGORY_FOOD_PRODUCTS = 'FOOD_PRODUCTS';

    /** Food retail and service */
    public const CATEGORY_FOOD_RETAIL_AND_SERVICE = 'FOOD_RETAIL_AND_SERVICE';

    /** Fragrances and perfumes */
    public const CATEGORY_FRAGRANCES_AND_PERFUMES = 'FRAGRANCES_AND_PERFUMES';

    /** Freezer and Locker Meat Provisioners */
    public const CATEGORY_FREEZER_AND_LOCKER_MEAT_PROVISIONERS = 'FREEZER_AND_LOCKER_MEAT_PROVISIONERS';

    /** Fuel Dealers-Fuel Oil, Wood & Coal */
    public const CATEGORY_FUEL_DEALERS_FUEL_OIL_WOOD_AND_COAL = 'FUEL_DEALERS_FUEL_OIL_WOOD_AND_COAL';

    /** Fuel Dealers - Non Automotive */
    public const CATEGORY_FUEL_DEALERS_NON_AUTOMOTIVE = 'FUEL_DEALERS_NON_AUTOMOTIVE';

    /** Funeral Services & Crematories */
    public const CATEGORY_FUNERAL_SERVICES_AND_CREMATORIES = 'FUNERAL_SERVICES_AND_CREMATORIES';

    /** Furnishing & Decorating */
    public const CATEGORY_FURNISHING_AND_DECORATING = 'FURNISHING_AND_DECORATING';

    /** Furniture */
    public const CATEGORY_FURNITURE = 'FURNITURE';

    /** Furriers and Fur Shops */
    public const CATEGORY_FURRIERS_AND_FUR_SHOPS = 'FURRIERS_AND_FUR_SHOPS';

    /** Gadgets & other electronics */
    public const CATEGORY_GADGETS_AND_OTHER_ELECTRONICS = 'GADGETS_AND_OTHER_ELECTRONICS';

    /** Gambling */
    public const CATEGORY_GAMBLING = 'GAMBLING';

    /** Game Software */
    public const CATEGORY_GAME_SOFTWARE = 'GAME_SOFTWARE';

    /** Games */
    public const CATEGORY_GAMES = 'GAMES';

    /** Garden supplies */
    public const CATEGORY_GARDEN_SUPPLIES = 'GARDEN_SUPPLIES';

    /** General */
    public const CATEGORY_GENERAL = 'GENERAL';

    /** General contractors */
    public const CATEGORY_GENERAL_CONTRACTORS = 'GENERAL_CONTRACTORS';

    /** General - Government */
    public const CATEGORY_GENERAL_GOVERNMENT = 'GENERAL_GOVERNMENT';

    /** General - Software */
    public const CATEGORY_GENERAL_SOFTWARE = 'GENERAL_SOFTWARE';

    /** General - Telecom */
    public const CATEGORY_GENERAL_TELECOM = 'GENERAL_TELECOM';

    /** Gifts and flowers */
    public const CATEGORY_GIFTS_AND_FLOWERS = 'GIFTS_AND_FLOWERS';

    /** Glass,Paint,And Wallpaper Stores */
    public const CATEGORY_GLASS_PAINT_AND_WALLPAPER_STORES = 'GLASS_PAINT_AND_WALLPAPER_STORES';

    /** Glassware, Crystal Stores */
    public const CATEGORY_GLASSWARE_CRYSTAL_STORES = 'GLASSWARE_CRYSTAL_STORES';

    /** Government */
    public const CATEGORY_GOVERNMENT = 'GOVERNMENT';

    /** Government IDs and Licenses */
    public const CATEGORY_GOVERNMENT_IDS_AND_LICENSES = 'GOVERNMENT_IDS_AND_LICENSES';

    /** Government Licensed On-Line Casinos - On-Line Gambling */
    public const CATEGORY_GOVERNMENT_LICENSED_ON_LINE_CASINOS_ON_LINE_GAMBLING = 'GOVERNMENT_LICENSED_ON_LINE_CASINOS_ON_LINE_GAMBLING';

    /** Government-Owned Lotteries */
    public const CATEGORY_GOVERNMENT_OWNED_LOTTERIES = 'GOVERNMENT_OWNED_LOTTERIES';

    /** Government services */
    public const CATEGORY_GOVERNMENT_SERVICES = 'GOVERNMENT_SERVICES';

    /** Graphic & Commercial Design */
    public const CATEGORY_GRAPHIC_AND_COMMERCIAL_DESIGN = 'GRAPHIC_AND_COMMERCIAL_DESIGN';

    /** Greeting Cards */
    public const CATEGORY_GREETING_CARDS = 'GREETING_CARDS';

    /** Grocery Stores & Supermarkets */
    public const CATEGORY_GROCERY_STORES_AND_SUPERMARKETS = 'GROCERY_STORES_AND_SUPERMARKETS';

    /** Hardware & Tools */
    public const CATEGORY_HARDWARE_AND_TOOLS = 'HARDWARE_AND_TOOLS';

    /** Hardware, Equipment, and Supplies */
    public const CATEGORY_HARDWARE_EQUIPMENT_AND_SUPPLIES = 'HARDWARE_EQUIPMENT_AND_SUPPLIES';

    /** Hazardous, Restricted and Perishable Items */
    public const CATEGORY_HAZARDOUS_RESTRICTED_AND_PERISHABLE_ITEMS = 'HAZARDOUS_RESTRICTED_AND_PERISHABLE_ITEMS';

    /** Health and beauty spas */
    public const CATEGORY_HEALTH_AND_BEAUTY_SPAS = 'HEALTH_AND_BEAUTY_SPAS';

    /** Health & Nutrition */
    public const CATEGORY_HEALTH_AND_NUTRITION = 'HEALTH_AND_NUTRITION';

    /** Health and personal care */
    public const CATEGORY_HEALTH_AND_PERSONAL_CARE = 'HEALTH_AND_PERSONAL_CARE';

    /** Hearing Aids Sales and Supplies */
    public const CATEGORY_HEARING_AIDS_SALES_AND_SUPPLIES = 'HEARING_AIDS_SALES_AND_SUPPLIES';

    /** Heating, Plumbing, AC */
    public const CATEGORY_HEATING_PLUMBING_AC = 'HEATING_PLUMBING_AC';

    /** High Risk Merchant */
    public const CATEGORY_HIGH_RISK_MERCHANT = 'HIGH_RISK_MERCHANT';

    /** Hiring services */
    public const CATEGORY_HIRING_SERVICES = 'HIRING_SERVICES';

    /** Hobbies, Toys & Games */
    public const CATEGORY_HOBBIES_TOYS_AND_GAMES = 'HOBBIES_TOYS_AND_GAMES';

    /** Home and garden */
    public const CATEGORY_HOME_AND_GARDEN = 'HOME_AND_GARDEN';

    /** Home Audio */
    public const CATEGORY_HOME_AUDIO = 'HOME_AUDIO';

    /** Home decor */
    public const CATEGORY_HOME_DECOR = 'HOME_DECOR';

    /** Home Electronics */
    public const CATEGORY_HOME_ELECTRONICS = 'HOME_ELECTRONICS';

    /** Hospitals */
    public const CATEGORY_HOSPITALS = 'HOSPITALS';

    /** Hotels/Motels/Inns/Resorts */
    public const CATEGORY_HOTELS_MOTELS_INNS_RESORTS = 'HOTELS_MOTELS_INNS_RESORTS';

    /** Housewares */
    public const CATEGORY_HOUSEWARES = 'HOUSEWARES';

    /** Human Parts and Remains */
    public const CATEGORY_HUMAN_PARTS_AND_REMAINS = 'HUMAN_PARTS_AND_REMAINS';

    /** Humorous Gifts & Novelties */
    public const CATEGORY_HUMOROUS_GIFTS_AND_NOVELTIES = 'HUMOROUS_GIFTS_AND_NOVELTIES';

    /** Hunting */
    public const CATEGORY_HUNTING = 'HUNTING';

    /** IDs, licenses, and passports */
    public const CATEGORY_IDS_LICENSES_AND_PASSPORTS = 'IDS_LICENSES_AND_PASSPORTS';

    /** Illegal Drugs & Paraphernalia */
    public const CATEGORY_ILLEGAL_DRUGS_AND_PARAPHERNALIA = 'ILLEGAL_DRUGS_AND_PARAPHERNALIA';

    /** Industrial */
    public const CATEGORY_INDUSTRIAL = 'INDUSTRIAL';

    /** Industrial and manufacturing supplies */
    public const CATEGORY_INDUSTRIAL_AND_MANUFACTURING_SUPPLIES = 'INDUSTRIAL_AND_MANUFACTURING_SUPPLIES';

    /** Insurance - auto and home */
    public const CATEGORY_INSURANCE_AUTO_AND_HOME = 'INSURANCE_AUTO_AND_HOME';

    /** Insurance - Direct */
    public const CATEGORY_INSURANCE_DIRECT = 'INSURANCE_DIRECT';

    /** Insurance - life and annuity */
    public const CATEGORY_INSURANCE_LIFE_AND_ANNUITY = 'INSURANCE_LIFE_AND_ANNUITY';

    /** Insurance Sales/Underwriting */
    public const CATEGORY_INSURANCE_SALES_UNDERWRITING = 'INSURANCE_SALES_UNDERWRITING';

    /** Insurance Underwriting, Premiums */
    public const CATEGORY_INSURANCE_UNDERWRITING_PREMIUMS = 'INSURANCE_UNDERWRITING_PREMIUMS';

    /** Internet & Network Services */
    public const CATEGORY_INTERNET_AND_NETWORK_SERVICES = 'INTERNET_AND_NETWORK_SERVICES';

    /** Intra-Company Purchases */
    public const CATEGORY_INTRA_COMPANY_PURCHASES = 'INTRA_COMPANY_PURCHASES';

    /** Laboratories-Dental/Medical */
    public const CATEGORY_LABORATORIES_DENTAL_MEDICAL = 'LABORATORIES_DENTAL_MEDICAL';

    /** Landscaping */
    public const CATEGORY_LANDSCAPING = 'LANDSCAPING';

    /** Landscaping And Horticultural Services */
    public const CATEGORY_LANDSCAPING_AND_HORTICULTURAL_SERVICES = 'LANDSCAPING_AND_HORTICULTURAL_SERVICES';

    /** Laundry, Cleaning Services */
    public const CATEGORY_LAUNDRY_CLEANING_SERVICES = 'LAUNDRY_CLEANING_SERVICES';

    /** Legal */
    public const CATEGORY_LEGAL = 'LEGAL';

    /** Legal services and attorneys */
    public const CATEGORY_LEGAL_SERVICES_AND_ATTORNEYS = 'LEGAL_SERVICES_AND_ATTORNEYS';

    /** Local delivery service */
    public const CATEGORY_LOCAL_DELIVERY_SERVICE = 'LOCAL_DELIVERY_SERVICE';

    /** Locksmith */
    public const CATEGORY_LOCKSMITH = 'LOCKSMITH';

    /** Lodging and accommodations */
    public const CATEGORY_LODGING_AND_ACCOMMODATIONS = 'LODGING_AND_ACCOMMODATIONS';

    /** Lottery and contests */
    public const CATEGORY_LOTTERY_AND_CONTESTS = 'LOTTERY_AND_CONTESTS';

    /** Luggage and leather goods */
    public const CATEGORY_LUGGAGE_AND_LEATHER_GOODS = 'LUGGAGE_AND_LEATHER_GOODS';

    /** Lumber & Building Materials */
    public const CATEGORY_LUMBER_AND_BUILDING_MATERIALS = 'LUMBER_AND_BUILDING_MATERIALS';

    /** Magazines */
    public const CATEGORY_MAGAZINES = 'MAGAZINES';

    /** Maintenance and repair services */
    public const CATEGORY_MAINTENANCE_AND_REPAIR_SERVICES = 'MAINTENANCE_AND_REPAIR_SERVICES';

    /** Makeup and cosmetics */
    public const CATEGORY_MAKEUP_AND_COSMETICS = 'MAKEUP_AND_COSMETICS';

    /** Manual Cash Disbursements */
    public const CATEGORY_MANUAL_CASH_DISBURSEMENTS = 'MANUAL_CASH_DISBURSEMENTS';

    /** Massage Parlors */
    public const CATEGORY_MASSAGE_PARLORS = 'MASSAGE_PARLORS';

    /** Medical */
    public const CATEGORY_MEDICAL = 'MEDICAL';

    /** Medical & Pharmaceutical */
    public const CATEGORY_MEDICAL_AND_PHARMACEUTICAL = 'MEDICAL_AND_PHARMACEUTICAL';

    /** Medical care */
    public const CATEGORY_MEDICAL_CARE = 'MEDICAL_CARE';

    /** Medical equipment and supplies */
    public const CATEGORY_MEDICAL_EQUIPMENT_AND_SUPPLIES = 'MEDICAL_EQUIPMENT_AND_SUPPLIES';

    /** Medical Services */
    public const CATEGORY_MEDICAL_SERVICES = 'MEDICAL_SERVICES';

    /** Meeting Planners */
    public const CATEGORY_MEETING_PLANNERS = 'MEETING_PLANNERS';

    /** Membership clubs and organizations */
    public const CATEGORY_MEMBERSHIP_CLUBS_AND_ORGANIZATIONS = 'MEMBERSHIP_CLUBS_AND_ORGANIZATIONS';

    /** Membership/Country Clubs/Golf */
    public const CATEGORY_MEMBERSHIP_COUNTRY_CLUBS_GOLF = 'MEMBERSHIP_COUNTRY_CLUBS_GOLF';

    /** Memorabilia */
    public const CATEGORY_MEMORABILIA = 'MEMORABILIA';

    /** Men's And Boy's Clothing And Accessory Stores */
    public const CATEGORY_MEN_AND_BOY_CLOTHING_AND_ACCESSORY_STORES = 'MEN_AND_BOY_CLOTHING_AND_ACCESSORY_STORES';

    /** Men's Clothing */
    public const CATEGORY_MEN_CLOTHING = 'MEN_CLOTHING';

    /** Merchandise */
    public const CATEGORY_MERCHANDISE = 'MERCHANDISE';

    /** Metaphysical */
    public const CATEGORY_METAPHYSICAL = 'METAPHYSICAL';

    /** Militaria */
    public const CATEGORY_MILITARIA = 'MILITARIA';

    /** Military and civil service uniforms */
    public const CATEGORY_MILITARY_AND_CIVIL_SERVICE_UNIFORMS = 'MILITARY_AND_CIVIL_SERVICE_UNIFORMS';

    /** Misc. Automotive,Aircraft,And Farm Equipment Dealers */
    public const CATEGORY_MISC__AUTOMOTIVE_AIRCRAFT_AND_FARM_EQUIPMENT_DEALERS = 'MISC._AUTOMOTIVE_AIRCRAFT_AND_FARM_EQUIPMENT_DEALERS';

    /** Misc. General Merchandise */
    public const CATEGORY_MISC__GENERAL_MERCHANDISE = 'MISC._GENERAL_MERCHANDISE';

    /** Miscellaneous General Services */
    public const CATEGORY_MISCELLANEOUS_GENERAL_SERVICES = 'MISCELLANEOUS_GENERAL_SERVICES';

    /** Miscellaneous Repair Shops And Related Services */
    public const CATEGORY_MISCELLANEOUS_REPAIR_SHOPS_AND_RELATED_SERVICES = 'MISCELLANEOUS_REPAIR_SHOPS_AND_RELATED_SERVICES';

    /** Model Kits */
    public const CATEGORY_MODEL_KITS = 'MODEL_KITS';

    /** Money Transfer - Member Financial Institution */
    public const CATEGORY_MONEY_TRANSFER_MEMBER_FINANCIAL_INSTITUTION = 'MONEY_TRANSFER_MEMBER_FINANCIAL_INSTITUTION';

    /** Money Transfer--Merchant */
    public const CATEGORY_MONEY_TRANSFER_MERCHANT = 'MONEY_TRANSFER_MERCHANT';

    /** Motion Picture Theaters */
    public const CATEGORY_MOTION_PICTURE_THEATERS = 'MOTION_PICTURE_THEATERS';

    /** Motor Freight Carriers & Trucking */
    public const CATEGORY_MOTOR_FREIGHT_CARRIERS_AND_TRUCKING = 'MOTOR_FREIGHT_CARRIERS_AND_TRUCKING';

    /** Motor Home And Recreational Vehicle Rental */
    public const CATEGORY_MOTOR_HOME_AND_RECREATIONAL_VEHICLE_RENTAL = 'MOTOR_HOME_AND_RECREATIONAL_VEHICLE_RENTAL';

    /** Motor Homes Dealers */
    public const CATEGORY_MOTOR_HOMES_DEALERS = 'MOTOR_HOMES_DEALERS';

    /** Motor Vehicle Supplies and New Parts */
    public const CATEGORY_MOTOR_VEHICLE_SUPPLIES_AND_NEW_PARTS = 'MOTOR_VEHICLE_SUPPLIES_AND_NEW_PARTS';

    /** Motorcycle Dealers */
    public const CATEGORY_MOTORCYCLE_DEALERS = 'MOTORCYCLE_DEALERS';

    /** Motorcycles */
    public const CATEGORY_MOTORCYCLES = 'MOTORCYCLES';

    /** Movie */
    public const CATEGORY_MOVIE = 'MOVIE';

    /** Movie tickets */
    public const CATEGORY_MOVIE_TICKETS = 'MOVIE_TICKETS';

    /** Moving and storage */
    public const CATEGORY_MOVING_AND_STORAGE = 'MOVING_AND_STORAGE';

    /** Multi-level marketing */
    public const CATEGORY_MULTI_LEVEL_MARKETING = 'MULTI_LEVEL_MARKETING';

    /** Music - CDs, cassettes and albums */
    public const CATEGORY_MUSIC_CDS_CASSETTES_AND_ALBUMS = 'MUSIC_CDS_CASSETTES_AND_ALBUMS';

    /** Music store - instruments and sheet music */
    public const CATEGORY_MUSIC_STORE_INSTRUMENTS_AND_SHEET_MUSIC = 'MUSIC_STORE_INSTRUMENTS_AND_SHEET_MUSIC';

    /** Networking */
    public const CATEGORY_NETWORKING = 'NETWORKING';

    /** New Age */
    public const CATEGORY_NEW_AGE = 'NEW_AGE';

    /** New parts and supplies - motor vehicle */
    public const CATEGORY_NEW_PARTS_AND_SUPPLIES_MOTOR_VEHICLE = 'NEW_PARTS_AND_SUPPLIES_MOTOR_VEHICLE';

    /** News Dealers and Newstands */
    public const CATEGORY_NEWS_DEALERS_AND_NEWSTANDS = 'NEWS_DEALERS_AND_NEWSTANDS';

    /** Non-durable goods */
    public const CATEGORY_NON_DURABLE_GOODS = 'NON_DURABLE_GOODS';

    /** Non-Fiction */
    public const CATEGORY_NON_FICTION = 'NON_FICTION';

    /** Non-Profit, Political & Religion */
    public const CATEGORY_NON_PROFIT_POLITICAL_AND_RELIGION = 'NON_PROFIT_POLITICAL_AND_RELIGION';

    /** Nonprofit */
    public const CATEGORY_NONPROFIT = 'NONPROFIT';

    /** Novelties */
    public const CATEGORY_NOVELTIES = 'NOVELTIES';

    /** Oem Software */
    public const CATEGORY_OEM_SOFTWARE = 'OEM_SOFTWARE';

    /** Office Supplies and Equipment */
    public const CATEGORY_OFFICE_SUPPLIES_AND_EQUIPMENT = 'OFFICE_SUPPLIES_AND_EQUIPMENT';

    /** Online Dating */
    public const CATEGORY_ONLINE_DATING = 'ONLINE_DATING';

    /** Online gaming */
    public const CATEGORY_ONLINE_GAMING = 'ONLINE_GAMING';

    /** Online gaming currency */
    public const CATEGORY_ONLINE_GAMING_CURRENCY = 'ONLINE_GAMING_CURRENCY';

    /** online services */
    public const CATEGORY_ONLINE_SERVICES = 'ONLINE_SERVICES';

    /** Ooutbound Telemarketing Merch */
    public const CATEGORY_OOUTBOUND_TELEMARKETING_MERCH = 'OOUTBOUND_TELEMARKETING_MERCH';

    /** Ophthalmologists/Optometrist */
    public const CATEGORY_OPHTHALMOLOGISTS_OPTOMETRIST = 'OPHTHALMOLOGISTS_OPTOMETRIST';

    /** Opticians And Dispensing */
    public const CATEGORY_OPTICIANS_AND_DISPENSING = 'OPTICIANS_AND_DISPENSING';

    /** Orthopedic Goods/Prosthetics */
    public const CATEGORY_ORTHOPEDIC_GOODS_PROSTHETICS = 'ORTHOPEDIC_GOODS_PROSTHETICS';

    /** Osteopaths */
    public const CATEGORY_OSTEOPATHS = 'OSTEOPATHS';

    /** Other */
    public const CATEGORY_OTHER = 'OTHER';

    /** Package Tour Operators */
    public const CATEGORY_PACKAGE_TOUR_OPERATORS = 'PACKAGE_TOUR_OPERATORS';

    /** Paintball */
    public const CATEGORY_PAINTBALL = 'PAINTBALL';

    /** Paints, Varnishes, and Supplies */
    public const CATEGORY_PAINTS_VARNISHES_AND_SUPPLIES = 'PAINTS_VARNISHES_AND_SUPPLIES';

    /** Parking Lots & Garages */
    public const CATEGORY_PARKING_LOTS_AND_GARAGES = 'PARKING_LOTS_AND_GARAGES';

    /** Parts and accessories */
    public const CATEGORY_PARTS_AND_ACCESSORIES = 'PARTS_AND_ACCESSORIES';

    /** Pawn Shops */
    public const CATEGORY_PAWN_SHOPS = 'PAWN_SHOPS';

    /** Paycheck lender or cash advance */
    public const CATEGORY_PAYCHECK_LENDER_OR_CASH_ADVANCE = 'PAYCHECK_LENDER_OR_CASH_ADVANCE';

    /** Peripherals */
    public const CATEGORY_PERIPHERALS = 'PERIPHERALS';

    /** Personalized Gifts */
    public const CATEGORY_PERSONALIZED_GIFTS = 'PERSONALIZED_GIFTS';

    /** Pet shops, pet food, and supplies */
    public const CATEGORY_PET_SHOPS_PET_FOOD_AND_SUPPLIES = 'PET_SHOPS_PET_FOOD_AND_SUPPLIES';

    /** Petroleum and Petroleum Products */
    public const CATEGORY_PETROLEUM_AND_PETROLEUM_PRODUCTS = 'PETROLEUM_AND_PETROLEUM_PRODUCTS';

    /** Pets and animals */
    public const CATEGORY_PETS_AND_ANIMALS = 'PETS_AND_ANIMALS';

    /** Photofinishing Laboratories,Photo Developing */
    public const CATEGORY_PHOTOFINISHING_LABORATORIES_PHOTO_DEVELOPING = 'PHOTOFINISHING_LABORATORIES_PHOTO_DEVELOPING';

    /** Photographic studios - portraits */
    public const CATEGORY_PHOTOGRAPHIC_STUDIOS_PORTRAITS = 'PHOTOGRAPHIC_STUDIOS_PORTRAITS';

    /** Photography */
    public const CATEGORY_PHOTOGRAPHY = 'PHOTOGRAPHY';

    /** Physical Good */
    public const CATEGORY_PHYSICAL_GOOD = 'PHYSICAL_GOOD';

    /** Picture/Video Production */
    public const CATEGORY_PICTURE_VIDEO_PRODUCTION = 'PICTURE_VIDEO_PRODUCTION';

    /** Piece Goods Notions and Other Dry Goods */
    public const CATEGORY_PIECE_GOODS_NOTIONS_AND_OTHER_DRY_GOODS = 'PIECE_GOODS_NOTIONS_AND_OTHER_DRY_GOODS';

    /** Plants and Seeds */
    public const CATEGORY_PLANTS_AND_SEEDS = 'PLANTS_AND_SEEDS';

    /** Plumbing & Heating Equipments & Supplies */
    public const CATEGORY_PLUMBING_AND_HEATING_EQUIPMENTS_AND_SUPPLIES = 'PLUMBING_AND_HEATING_EQUIPMENTS_AND_SUPPLIES';

    /** Police-Related Items */
    public const CATEGORY_POLICE_RELATED_ITEMS = 'POLICE_RELATED_ITEMS';

    /** Politcal Organizations */
    public const CATEGORY_POLITICAL_ORGANIZATIONS = 'POLITICAL_ORGANIZATIONS';

    /** Postal Services - Government Only */
    public const CATEGORY_POSTAL_SERVICES_GOVERNMENT_ONLY = 'POSTAL_SERVICES_GOVERNMENT_ONLY';

    /** Posters */
    public const CATEGORY_POSTERS = 'POSTERS';

    /** Prepaid and stored value cards */
    public const CATEGORY_PREPAID_AND_STORED_VALUE_CARDS = 'PREPAID_AND_STORED_VALUE_CARDS';

    /** Prescription Drugs */
    public const CATEGORY_PRESCRIPTION_DRUGS = 'PRESCRIPTION_DRUGS';

    /** Promotional Items */
    public const CATEGORY_PROMOTIONAL_ITEMS = 'PROMOTIONAL_ITEMS';

    /** Public Warehousing and Storage */
    public const CATEGORY_PUBLIC_WAREHOUSING_AND_STORAGE = 'PUBLIC_WAREHOUSING_AND_STORAGE';

    /** Publishing and printing */
    public const CATEGORY_PUBLISHING_AND_PRINTING = 'PUBLISHING_AND_PRINTING';

    /** Publishing Services */
    public const CATEGORY_PUBLISHING_SERVICES = 'PUBLISHING_SERVICES';

    /** Radar Dectors */
    public const CATEGORY_RADAR_DECTORS = 'RADAR_DECTORS';

    /** Radio, television, and stereo repair */
    public const CATEGORY_RADIO_TELEVISION_AND_STEREO_REPAIR = 'RADIO_TELEVISION_AND_STEREO_REPAIR';

    /** Real Estate */
    public const CATEGORY_REAL_ESTATE = 'REAL_ESTATE';

    /** Real estate agent */
    public const CATEGORY_REAL_ESTATE_AGENT = 'REAL_ESTATE_AGENT';

    /** Real Estate Agents And Managers - Rentals */
    public const CATEGORY_REAL_ESTATE_AGENTS_AND_MANAGERS_RENTALS = 'REAL_ESTATE_AGENTS_AND_MANAGERS_RENTALS';

    /** Religion and spirituality for profit */
    public const CATEGORY_RELIGION_AND_SPIRITUALITY_FOR_PROFIT = 'RELIGION_AND_SPIRITUALITY_FOR_PROFIT';

    /** Religious */
    public const CATEGORY_RELIGIOUS = 'RELIGIOUS';

    /** Religious Organizations */
    public const CATEGORY_RELIGIOUS_ORGANIZATIONS = 'RELIGIOUS_ORGANIZATIONS';

    /** Remittance */
    public const CATEGORY_REMITTANCE = 'REMITTANCE';

    /** Rental property management */
    public const CATEGORY_RENTAL_PROPERTY_MANAGEMENT = 'RENTAL_PROPERTY_MANAGEMENT';

    /** Residential */
    public const CATEGORY_RESIDENTIAL = 'RESIDENTIAL';

    /** Retail */
    public const CATEGORY_RETAIL = 'RETAIL';

    /** Retail - fine jewelry and watches */
    public const CATEGORY_RETAIL_FINE_JEWELRY_AND_WATCHES = 'RETAIL_FINE_JEWELRY_AND_WATCHES';

    /** Reupholstery and furniture repair */
    public const CATEGORY_REUPHOLSTERY_AND_FURNITURE_REPAIR = 'REUPHOLSTERY_AND_FURNITURE_REPAIR';

    /** Rings */
    public const CATEGORY_RINGS = 'RINGS';

    /** Roofing/Siding, Sheet Metal */
    public const CATEGORY_ROOFING_SIDING_SHEET_METAL = 'ROOFING_SIDING_SHEET_METAL';

    /** Rugs & Carpets */
    public const CATEGORY_RUGS_AND_CARPETS = 'RUGS_AND_CARPETS';

    /** Schools and Colleges */
    public const CATEGORY_SCHOOLS_AND_COLLEGES = 'SCHOOLS_AND_COLLEGES';

    /** Science Fiction */
    public const CATEGORY_SCIENCE_FICTION = 'SCIENCE_FICTION';

    /** Scrapbooking */
    public const CATEGORY_SCRAPBOOKING = 'SCRAPBOOKING';

    /** Sculptures */
    public const CATEGORY_SCULPTURES = 'SCULPTURES';

    /** Securities - Brokers And Dealers */
    public const CATEGORY_SECURITIES_BROKERS_AND_DEALERS = 'SECURITIES_BROKERS_AND_DEALERS';

    /** Security and surveillance */
    public const CATEGORY_SECURITY_AND_SURVEILLANCE = 'SECURITY_AND_SURVEILLANCE';

    /** Security and surveillance equipment */
    public const CATEGORY_SECURITY_AND_SURVEILLANCE_EQUIPMENT = 'SECURITY_AND_SURVEILLANCE_EQUIPMENT';

    /** Security brokers and dealers */
    public const CATEGORY_SECURITY_BROKERS_AND_DEALERS = 'SECURITY_BROKERS_AND_DEALERS';

    /** Seminars */
    public const CATEGORY_SEMINARS = 'SEMINARS';

    /** Service Stations */
    public const CATEGORY_SERVICE_STATIONS = 'SERVICE_STATIONS';

    /** Services */
    public const CATEGORY_SERVICES = 'SERVICES';

    /** Sewing,Needlework,Fabric And Piece Goods Stores */
    public const CATEGORY_SEWING_NEEDLEWORK_FABRIC_AND_PIECE_GOODS_STORES = 'SEWING_NEEDLEWORK_FABRIC_AND_PIECE_GOODS_STORES';

    /** Shipping & Packaging */
    public const CATEGORY_SHIPPING_AND_PACKING = 'SHIPPING_AND_PACKING';

    /** Shoe Repair/Hat Cleaning */
    public const CATEGORY_SHOE_REPAIR_HAT_CLEANING = 'SHOE_REPAIR_HAT_CLEANING';

    /** Shoe Stores */
    public const CATEGORY_SHOE_STORES = 'SHOE_STORES';

    /** Shoes */
    public const CATEGORY_SHOES = 'SHOES';

    /** Snowmobile Dealers */
    public const CATEGORY_SNOWMOBILE_DEALERS = 'SNOWMOBILE_DEALERS';

    /** Software */
    public const CATEGORY_SOFTWARE = 'SOFTWARE';

    /** Specialty and misc. food stores */
    public const CATEGORY_SPECIALTY_AND_MISC__FOOD_STORES = 'SPECIALTY_AND_MISC._FOOD_STORES';

    /** Specialty Cleaning, Polishing And Sanitation Preparations */
    public const CATEGORY_SPECIALTY_CLEANING_POLISHING_AND_SANITATION_PREPARATIONS = 'SPECIALTY_CLEANING_POLISHING_AND_SANITATION_PREPARATIONS';

    /** Specialty or rare pets */
    public const CATEGORY_SPECIALTY_OR_RARE_PETS = 'SPECIALTY_OR_RARE_PETS';

    /** Sport games and toys */
    public const CATEGORY_SPORT_GAMES_AND_TOYS = 'SPORT_GAMES_AND_TOYS';

    /** Sporting And Recreational Camps */
    public const CATEGORY_SPORTING_AND_RECREATIONAL_CAMPS = 'SPORTING_AND_RECREATIONAL_CAMPS';

    /** Sporting Goods */
    public const CATEGORY_SPORTING_GOODS = 'SPORTING_GOODS';

    /** Sports and outdoors */
    public const CATEGORY_SPORTS_AND_OUTDOORS = 'SPORTS_AND_OUTDOORS';

    /** Sports & Recreation */
    public const CATEGORY_SPORTS_AND_RECREATION = 'SPORTS_AND_RECREATION';

    /** Stamp and coin */
    public const CATEGORY_STAMP_AND_COIN = 'STAMP_AND_COIN';

    /** Stationary, printing, and writing paper */
    public const CATEGORY_STATIONARY_PRINTING_AND_WRITING_PAPER = 'STATIONARY_PRINTING_AND_WRITING_PAPER';

    /** Stenographic and secretarial support services */
    public const CATEGORY_STENOGRAPHIC_AND_SECRETARIAL_SUPPORT_SERVICES = 'STENOGRAPHIC_AND_SECRETARIAL_SUPPORT_SERVICES';

    /** Stocks, Bonds, Securities and Related Certificates */
    public const CATEGORY_STOCKS_BONDS_SECURITIES_AND_RELATED_CERTIFICATES = 'STOCKS_BONDS_SECURITIES_AND_RELATED_CERTIFICATES';

    /** Stored Value Cards */
    public const CATEGORY_STORED_VALUE_CARDS = 'STORED_VALUE_CARDS';

    /** Supplies */
    public const CATEGORY_SUPPLIES = 'SUPPLIES';

    /** Supplies & Toys */
    public const CATEGORY_SUPPLIES_AND_TOYS = 'SUPPLIES_AND_TOYS';

    /** Surveillance Equipment */
    public const CATEGORY_SURVEILLANCE_EQUIPMENT = 'SURVEILLANCE_EQUIPMENT';

    /** Swimming Pools & Spas */
    public const CATEGORY_SWIMMING_POOLS_AND_SPAS = 'SWIMMING_POOLS_AND_SPAS';

    /** Swimming Pools-Sales,Supplies,Services */
    public const CATEGORY_SWIMMING_POOLS_SALES_SUPPLIES_SERVICES = 'SWIMMING_POOLS_SALES_SUPPLIES_SERVICES';

    /** Tailors and alterations */
    public const CATEGORY_TAILORS_AND_ALTERATIONS = 'TAILORS_AND_ALTERATIONS';

    /** Tax Payments */
    public const CATEGORY_TAX_PAYMENTS = 'TAX_PAYMENTS';

    /** Tax Payments - Government Agencies */
    public const CATEGORY_TAX_PAYMENTS_GOVERNMENT_AGENCIES = 'TAX_PAYMENTS_GOVERNMENT_AGENCIES';

    /** Taxicabs and limousines */
    public const CATEGORY_TAXICABS_AND_LIMOUSINES = 'TAXICABS_AND_LIMOUSINES';

    /** Telecommunication Services */
    public const CATEGORY_TELECOMMUNICATION_SERVICES = 'TELECOMMUNICATION_SERVICES';

    /** Telephone Cards */
    public const CATEGORY_TELEPHONE_CARDS = 'TELEPHONE_CARDS';

    /** Telephone Equipment */
    public const CATEGORY_TELEPHONE_EQUIPMENT = 'TELEPHONE_EQUIPMENT';

    /** Telephone Services */
    public const CATEGORY_TELEPHONE_SERVICES = 'TELEPHONE_SERVICES';

    /** Theater */
    public const CATEGORY_THEATER = 'THEATER';

    /** Tire Retreading and Repair */
    public const CATEGORY_TIRE_RETREADING_AND_REPAIR = 'TIRE_RETREADING_AND_REPAIR';

    /** Toll or Bridge Fees */
    public const CATEGORY_TOLL_OR_BRIDGE_FEES = 'TOLL_OR_BRIDGE_FEES';

    /** Tools and equipment */
    public const CATEGORY_TOOLS_AND_EQUIPMENT = 'TOOLS_AND_EQUIPMENT';

    /** Tourist Attractions And Exhibits */
    public const CATEGORY_TOURIST_ATTRACTIONS_AND_EXHIBITS = 'TOURIST_ATTRACTIONS_AND_EXHIBITS';

    /** Towing service */
    public const CATEGORY_TOWING_SERVICE = 'TOWING_SERVICE';

    /** Toys and games */
    public const CATEGORY_TOYS_AND_GAMES = 'TOYS_AND_GAMES';

    /** Trade And Vocational Schools */
    public const CATEGORY_TRADE_AND_VOCATIONAL_SCHOOLS = 'TRADE_AND_VOCATIONAL_SCHOOLS';

    /** Trademark Infringement */
    public const CATEGORY_TRADEMARK_INFRINGEMENT = 'TRADEMARK_INFRINGEMENT';

    /** Trailer Parks And Campgrounds */
    public const CATEGORY_TRAILER_PARKS_AND_CAMPGROUNDS = 'TRAILER_PARKS_AND_CAMPGROUNDS';

    /** Training services */
    public const CATEGORY_TRAINING_SERVICES = 'TRAINING_SERVICES';

    /** Transportation Services */
    public const CATEGORY_TRANSPORTATION_SERVICES = 'TRANSPORTATION_SERVICES';

    /** Travel */
    public const CATEGORY_TRAVEL = 'TRAVEL';

    /** Truck And Utility Trailer Rentals */
    public const CATEGORY_TRUCK_AND_UTILITY_TRAILER_RENTALS = 'TRUCK_AND_UTILITY_TRAILER_RENTALS';

    /** Truck Stop */
    public const CATEGORY_TRUCK_STOP = 'TRUCK_STOP';

    /** Typesetting, Plate Making, and Related Services */
    public const CATEGORY_TYPESETTING_PLATE_MAKING_AND_RELATED_SERVICES = 'TYPESETTING_PLATE_MAKING_AND_RELATED_SERVICES';

    /** Used Merchandise And Secondhand Stores */
    public const CATEGORY_USED_MERCHANDISE_AND_SECONDHAND_STORES = 'USED_MERCHANDISE_AND_SECONDHAND_STORES';

    /** Used parts - motor vehicle */
    public const CATEGORY_USED_PARTS_MOTOR_VEHICLE = 'USED_PARTS_MOTOR_VEHICLE';

    /** Utilities */
    public const CATEGORY_UTILITIES = 'UTILITIES';

    /** Utilities - Electric,Gas,Water,Sanitary */
    public const CATEGORY_UTILITIES_ELECTRIC_GAS_WATER_SANITARY = 'UTILITIES_ELECTRIC_GAS_WATER_SANITARY';

    /** Variety Stores */
    public const CATEGORY_VARIETY_STORES = 'VARIETY_STORES';

    /** Vehicle sales */
    public const CATEGORY_VEHICLE_SALES = 'VEHICLE_SALES';

    /** Vehicle service and accessories */
    public const CATEGORY_VEHICLE_SERVICE_AND_ACCESSORIES = 'VEHICLE_SERVICE_AND_ACCESSORIES';

    /** Video Equipment */
    public const CATEGORY_VIDEO_EQUIPMENT = 'VIDEO_EQUIPMENT';

    /** Video Game Arcades/Establish */
    public const CATEGORY_VIDEO_GAME_ARCADES_ESTABLISH = 'VIDEO_GAME_ARCADES_ESTABLISH';

    /** Video Games & Systems */
    public const CATEGORY_VIDEO_GAMES_AND_SYSTEMS = 'VIDEO_GAMES_AND_SYSTEMS';

    /** Video Tape Rental Stores */
    public const CATEGORY_VIDEO_TAPE_RENTAL_STORES = 'VIDEO_TAPE_RENTAL_STORES';

    /** Vintage and Collectible Vehicles */
    public const CATEGORY_VINTAGE_AND_COLLECTIBLE_VEHICLES = 'VINTAGE_AND_COLLECTIBLE_VEHICLES';

    /** Vintage and collectibles */
    public const CATEGORY_VINTAGE_AND_COLLECTIBLES = 'VINTAGE_AND_COLLECTIBLES';

    /** Vitamins & Supplements */
    public const CATEGORY_VITAMINS_AND_SUPPLEMENTS = 'VITAMINS_AND_SUPPLEMENTS';

    /** Vocational and trade schools */
    public const CATEGORY_VOCATIONAL_AND_TRADE_SCHOOLS = 'VOCATIONAL_AND_TRADE_SCHOOLS';

    /** Watch, clock, and jewelry repair */
    public const CATEGORY_WATCH_CLOCK_AND_JEWELRY_REPAIR = 'WATCH_CLOCK_AND_JEWELRY_REPAIR';

    /** Web hosting and design */
    public const CATEGORY_WEB_HOSTING_AND_DESIGN = 'WEB_HOSTING_AND_DESIGN';

    /** Welding Repair */
    public const CATEGORY_WELDING_REPAIR = 'WELDING_REPAIR';

    /** Wholesale Clubs */
    public const CATEGORY_WHOLESALE_CLUBS = 'WHOLESALE_CLUBS';

    /** Wholesale Florist Suppliers */
    public const CATEGORY_WHOLESALE_FLORIST_SUPPLIERS = 'WHOLESALE_FLORIST_SUPPLIERS';

    /** Wholesale Prescription Drugs */
    public const CATEGORY_WHOLESALE_PRESCRIPTION_DRUGS = 'WHOLESALE_PRESCRIPTION_DRUGS';

    /** Wildlife Products */
    public const CATEGORY_WILDLIFE_PRODUCTS = 'WILDLIFE_PRODUCTS';

    /** Wire Transfer */
    public const CATEGORY_WIRE_TRANSFER = 'WIRE_TRANSFER';

    /** Wire transfer and money order */
    public const CATEGORY_WIRE_TRANSFER_AND_MONEY_ORDER = 'WIRE_TRANSFER_AND_MONEY_ORDER';

    /** Women's Accessory/Speciality */
    public const CATEGORY_WOMEN_ACCESSORY_SPECIALITY = 'WOMEN_ACCESSORY_SPECIALITY';

    /** Women's clothing */
    public const CATEGORY_WOMEN_CLOTHING = 'WOMEN_CLOTHING';

    /**
     * The ID of the product.
     *
     * @var string | null
     * minLength: 6
     * maxLength: 50
     */
    public $id;

    /**
     * The product name.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $name;

    /**
     * The product description.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 256
     */
    public $description;

    /**
     * The product type. Indicates whether the product is physical or digital goods, or a service.
     *
     * use one of constants defined in this class to set the value:
     * @see TYPE_PHYSICAL
     * @see TYPE_DIGITAL
     * @see TYPE_SERVICE
     * @var string | null
     * minLength: 1
     * maxLength: 24
     */
    public $type = 'PHYSICAL';

    /**
     * The product category.
     *
     * use one of constants defined in this class to set the value:
     * @see CATEGORY_AC_REFRIGERATION_REPAIR
     * @see CATEGORY_ACADEMIC_SOFTWARE
     * @see CATEGORY_ACCESSORIES
     * @see CATEGORY_ACCOUNTING
     * @see CATEGORY_ADULT
     * @see CATEGORY_ADVERTISING
     * @see CATEGORY_AFFILIATED_AUTO_RENTAL
     * @see CATEGORY_AGENCIES
     * @see CATEGORY_AGGREGATORS
     * @see CATEGORY_AGRICULTURAL_COOPERATIVE_FOR_MAIL_ORDER
     * @see CATEGORY_AIR_CARRIERS_AIRLINES
     * @see CATEGORY_AIRLINES
     * @see CATEGORY_AIRPORTS_FLYING_FIELDS
     * @see CATEGORY_ALCOHOLIC_BEVERAGES
     * @see CATEGORY_AMUSEMENT_PARKS_CARNIVALS
     * @see CATEGORY_ANIMATION
     * @see CATEGORY_ANTIQUES
     * @see CATEGORY_APPLIANCES
     * @see CATEGORY_AQUARIAMS_SEAQUARIUMS_DOLPHINARIUMS
     * @see CATEGORY_ARCHITECTURAL_ENGINEERING_AND_SURVEYING_SERVICES
     * @see CATEGORY_ART_AND_CRAFT_SUPPLIES
     * @see CATEGORY_ART_DEALERS_AND_GALLERIES
     * @see CATEGORY_ARTIFACTS_GRAVE_RELATED_AND_NATIVE_AMERICAN_CRAFTS
     * @see CATEGORY_ARTS_AND_CRAFTS
     * @see CATEGORY_ARTS_CRAFTS_AND_COLLECTIBLES
     * @see CATEGORY_AUDIO_BOOKS
     * @see CATEGORY_AUTO_ASSOCIATIONS_CLUBS
     * @see CATEGORY_AUTO_DEALER_USED_ONLY
     * @see CATEGORY_AUTO_RENTALS
     * @see CATEGORY_AUTO_SERVICE
     * @see CATEGORY_AUTOMATED_FUEL_DISPENSERS
     * @see CATEGORY_AUTOMOBILE_ASSOCIATIONS
     * @see CATEGORY_AUTOMOTIVE
     * @see CATEGORY_AUTOMOTIVE_REPAIR_SHOPS_NON_DEALER
     * @see CATEGORY_AUTOMOTIVE_TOP_AND_BODY_SHOPS
     * @see CATEGORY_AVIATION
     * @see CATEGORY_BABIES_CLOTHING_AND_SUPPLIES
     * @see CATEGORY_BABY
     * @see CATEGORY_BANDS_ORCHESTRAS_ENTERTAINERS
     * @see CATEGORY_BARBIES
     * @see CATEGORY_BATH_AND_BODY
     * @see CATEGORY_BATTERIES
     * @see CATEGORY_BEAN_BABIES
     * @see CATEGORY_BEAUTY
     * @see CATEGORY_BEAUTY_AND_FRAGRANCES
     * @see CATEGORY_BED_AND_BATH
     * @see CATEGORY_BICYCLE_SHOPS_SALES_AND_SERVICE
     * @see CATEGORY_BICYCLES_AND_ACCESSORIES
     * @see CATEGORY_BILLIARD_POOL_ESTABLISHMENTS
     * @see CATEGORY_BOAT_DEALERS
     * @see CATEGORY_BOAT_RENTALS_AND_LEASING
     * @see CATEGORY_BOATING_SAILING_AND_ACCESSORIES
     * @see CATEGORY_BOOKS
     * @see CATEGORY_BOOKS_AND_MAGAZINES
     * @see CATEGORY_BOOKS_MANUSCRIPTS
     * @see CATEGORY_BOOKS_PERIODICALS_AND_NEWSPAPERS
     * @see CATEGORY_BOWLING_ALLEYS
     * @see CATEGORY_BULLETIN_BOARD
     * @see CATEGORY_BUS_LINE
     * @see CATEGORY_BUS_LINES_CHARTERS_TOUR_BUSES
     * @see CATEGORY_BUSINESS
     * @see CATEGORY_BUSINESS_AND_SECRETARIAL_SCHOOLS
     * @see CATEGORY_BUYING_AND_SHOPPING_SERVICES_AND_CLUBS
     * @see CATEGORY_CABLE_SATELLITE_AND_OTHER_PAY_TELEVISION_AND_RADIO_SERVICES
     * @see CATEGORY_CABLE_SATELLITE_AND_OTHER_PAY_TV_AND_RADIO
     * @see CATEGORY_CAMERA_AND_PHOTOGRAPHIC_SUPPLIES
     * @see CATEGORY_CAMERAS
     * @see CATEGORY_CAMERAS_AND_PHOTOGRAPHY
     * @see CATEGORY_CAMPER_RECREATIONAL_AND_UTILITY_TRAILER_DEALERS
     * @see CATEGORY_CAMPING_AND_OUTDOORS
     * @see CATEGORY_CAMPING_AND_SURVIVAL
     * @see CATEGORY_CAR_AND_TRUCK_DEALERS
     * @see CATEGORY_CAR_AND_TRUCK_DEALERS_USED_ONLY
     * @see CATEGORY_CAR_AUDIO_AND_ELECTRONICS
     * @see CATEGORY_CAR_RENTAL_AGENCY
     * @see CATEGORY_CATALOG_MERCHANT
     * @see CATEGORY_CATALOG_RETAIL_MERCHANT
     * @see CATEGORY_CATERING_SERVICES
     * @see CATEGORY_CHARITY
     * @see CATEGORY_CHECK_CASHIER
     * @see CATEGORY_CHILD_CARE_SERVICES
     * @see CATEGORY_CHILDREN_BOOKS
     * @see CATEGORY_CHIROPODISTS_PODIATRISTS
     * @see CATEGORY_CHIROPRACTORS
     * @see CATEGORY_CIGAR_STORES_AND_STANDS
     * @see CATEGORY_CIVIC_SOCIAL_FRATERNAL_ASSOCIATIONS
     * @see CATEGORY_CIVIL_SOCIAL_FRAT_ASSOCIATIONS
     * @see CATEGORY_CLOTHING
     * @see CATEGORY_CLOTHING_ACCESSORIES_AND_SHOES
     * @see CATEGORY_CLOTHING_RENTAL
     * @see CATEGORY_COFFEE_AND_TEA
     * @see CATEGORY_COIN_OPERATED_BANKS_AND_CASINOS
     * @see CATEGORY_COLLECTIBLES
     * @see CATEGORY_COLLECTION_AGENCY
     * @see CATEGORY_COLLEGES_AND_UNIVERSITIES
     * @see CATEGORY_COMMERCIAL_EQUIPMENT
     * @see CATEGORY_COMMERCIAL_FOOTWEAR
     * @see CATEGORY_COMMERCIAL_PHOTOGRAPHY
     * @see CATEGORY_COMMERCIAL_PHOTOGRAPHY_ART_AND_GRAPHICS
     * @see CATEGORY_COMMERCIAL_SPORTS_PROFESSIONA
     * @see CATEGORY_COMMODITIES_AND_FUTURES_EXCHANGE
     * @see CATEGORY_COMPUTER_AND_DATA_PROCESSING_SERVICES
     * @see CATEGORY_COMPUTER_HARDWARE_AND_SOFTWARE
     * @see CATEGORY_COMPUTER_MAINTENANCE_REPAIR_AND_SERVICES_NOT_ELSEWHERE_CLAS
     * @see CATEGORY_CONSTRUCTION
     * @see CATEGORY_CONSTRUCTION_MATERIALS_NOT_ELSEWHERE_CLASSIFIED
     * @see CATEGORY_CONSULTING_SERVICES
     * @see CATEGORY_CONSUMER_CREDIT_REPORTING_AGENCIES
     * @see CATEGORY_CONVALESCENT_HOMES
     * @see CATEGORY_COSMETIC_STORES
     * @see CATEGORY_COUNSELING_SERVICES_DEBT_MARRIAGE_PERSONAL
     * @see CATEGORY_COUNTERFEIT_CURRENCY_AND_STAMPS
     * @see CATEGORY_COUNTERFEIT_ITEMS
     * @see CATEGORY_COUNTRY_CLUBS
     * @see CATEGORY_COURIER_SERVICES
     * @see CATEGORY_COURIER_SERVICES_AIR_AND_GROUND_AND_FREIGHT_FORWARDERS
     * @see CATEGORY_COURT_COSTS_ALIMNY_CHILD_SUPT
     * @see CATEGORY_COURT_COSTS_INCLUDING_ALIMONY_AND_CHILD_SUPPORT_COURTS_OF_LAW
     * @see CATEGORY_CREDIT_CARDS
     * @see CATEGORY_CREDIT_UNION
     * @see CATEGORY_CULTURE_AND_RELIGION
     * @see CATEGORY_DAIRY_PRODUCTS_STORES
     * @see CATEGORY_DANCE_HALLS_STUDIOS_AND_SCHOOLS
     * @see CATEGORY_DECORATIVE
     * @see CATEGORY_DENTAL
     * @see CATEGORY_DENTISTS_AND_ORTHODONTISTS
     * @see CATEGORY_DEPARTMENT_STORES
     * @see CATEGORY_DESKTOP_PCS
     * @see CATEGORY_DEVICES
     * @see CATEGORY_DIECAST_TOYS_VEHICLES
     * @see CATEGORY_DIGITAL_GAMES
     * @see CATEGORY_DIGITAL_MEDIA_BOOKS_MOVIES_MUSIC
     * @see CATEGORY_DIRECT_MARKETING
     * @see CATEGORY_DIRECT_MARKETING_CATALOG_MERCHANT
     * @see CATEGORY_DIRECT_MARKETING_INBOUND_TELE
     * @see CATEGORY_DIRECT_MARKETING_OUTBOUND_TELE
     * @see CATEGORY_DIRECT_MARKETING_SUBSCRIPTION
     * @see CATEGORY_DISCOUNT_STORES
     * @see CATEGORY_DOOR_TO_DOOR_SALES
     * @see CATEGORY_DRAPERY_WINDOW_COVERING_AND_UPHOLSTERY
     * @see CATEGORY_DRINKING_PLACES
     * @see CATEGORY_DRUGSTORE
     * @see CATEGORY_DURABLE_GOODS
     * @see CATEGORY_ECOMMERCE_DEVELOPMENT
     * @see CATEGORY_ECOMMERCE_SERVICES
     * @see CATEGORY_EDUCATIONAL_AND_TEXTBOOKS
     * @see CATEGORY_ELECTRIC_RAZOR_STORES
     * @see CATEGORY_ELECTRICAL_AND_SMALL_APPLIANCE_REPAIR
     * @see CATEGORY_ELECTRICAL_CONTRACTORS
     * @see CATEGORY_ELECTRICAL_PARTS_AND_EQUIPMENT
     * @see CATEGORY_ELECTRONIC_CASH
     * @see CATEGORY_ELEMENTARY_AND_SECONDARY_SCHOOLS
     * @see CATEGORY_EMPLOYMENT
     * @see CATEGORY_ENTERTAINERS
     * @see CATEGORY_ENTERTAINMENT_AND_MEDIA
     * @see CATEGORY_EQUIP_TOOL_FURNITURE_AND_APPLIANCE_RENTAL_AND_LEASING
     * @see CATEGORY_ESCROW
     * @see CATEGORY_EVENT_AND_WEDDING_PLANNING
     * @see CATEGORY_EXERCISE_AND_FITNESS
     * @see CATEGORY_EXERCISE_EQUIPMENT
     * @see CATEGORY_EXTERMINATING_AND_DISINFECTING_SERVICES
     * @see CATEGORY_FABRICS_AND_SEWING
     * @see CATEGORY_FAMILY_CLOTHING_STORES
     * @see CATEGORY_FASHION_JEWELRY
     * @see CATEGORY_FAST_FOOD_RESTAURANTS
     * @see CATEGORY_FICTION_AND_NONFICTION
     * @see CATEGORY_FINANCE_COMPANY
     * @see CATEGORY_FINANCIAL_AND_INVESTMENT_ADVICE
     * @see CATEGORY_FINANCIAL_INSTITUTIONS_MERCHANDISE_AND_SERVICES
     * @see CATEGORY_FIREARM_ACCESSORIES
     * @see CATEGORY_FIREARMS_WEAPONS_AND_KNIVES
     * @see CATEGORY_FIREPLACE_AND_FIREPLACE_SCREENS
     * @see CATEGORY_FIREWORKS
     * @see CATEGORY_FISHING
     * @see CATEGORY_FLORISTS
     * @see CATEGORY_FLOWERS
     * @see CATEGORY_FOOD_DRINK_AND_NUTRITION
     * @see CATEGORY_FOOD_PRODUCTS
     * @see CATEGORY_FOOD_RETAIL_AND_SERVICE
     * @see CATEGORY_FRAGRANCES_AND_PERFUMES
     * @see CATEGORY_FREEZER_AND_LOCKER_MEAT_PROVISIONERS
     * @see CATEGORY_FUEL_DEALERS_FUEL_OIL_WOOD_AND_COAL
     * @see CATEGORY_FUEL_DEALERS_NON_AUTOMOTIVE
     * @see CATEGORY_FUNERAL_SERVICES_AND_CREMATORIES
     * @see CATEGORY_FURNISHING_AND_DECORATING
     * @see CATEGORY_FURNITURE
     * @see CATEGORY_FURRIERS_AND_FUR_SHOPS
     * @see CATEGORY_GADGETS_AND_OTHER_ELECTRONICS
     * @see CATEGORY_GAMBLING
     * @see CATEGORY_GAME_SOFTWARE
     * @see CATEGORY_GAMES
     * @see CATEGORY_GARDEN_SUPPLIES
     * @see CATEGORY_GENERAL
     * @see CATEGORY_GENERAL_CONTRACTORS
     * @see CATEGORY_GENERAL_GOVERNMENT
     * @see CATEGORY_GENERAL_SOFTWARE
     * @see CATEGORY_GENERAL_TELECOM
     * @see CATEGORY_GIFTS_AND_FLOWERS
     * @see CATEGORY_GLASS_PAINT_AND_WALLPAPER_STORES
     * @see CATEGORY_GLASSWARE_CRYSTAL_STORES
     * @see CATEGORY_GOVERNMENT
     * @see CATEGORY_GOVERNMENT_IDS_AND_LICENSES
     * @see CATEGORY_GOVERNMENT_LICENSED_ON_LINE_CASINOS_ON_LINE_GAMBLING
     * @see CATEGORY_GOVERNMENT_OWNED_LOTTERIES
     * @see CATEGORY_GOVERNMENT_SERVICES
     * @see CATEGORY_GRAPHIC_AND_COMMERCIAL_DESIGN
     * @see CATEGORY_GREETING_CARDS
     * @see CATEGORY_GROCERY_STORES_AND_SUPERMARKETS
     * @see CATEGORY_HARDWARE_AND_TOOLS
     * @see CATEGORY_HARDWARE_EQUIPMENT_AND_SUPPLIES
     * @see CATEGORY_HAZARDOUS_RESTRICTED_AND_PERISHABLE_ITEMS
     * @see CATEGORY_HEALTH_AND_BEAUTY_SPAS
     * @see CATEGORY_HEALTH_AND_NUTRITION
     * @see CATEGORY_HEALTH_AND_PERSONAL_CARE
     * @see CATEGORY_HEARING_AIDS_SALES_AND_SUPPLIES
     * @see CATEGORY_HEATING_PLUMBING_AC
     * @see CATEGORY_HIGH_RISK_MERCHANT
     * @see CATEGORY_HIRING_SERVICES
     * @see CATEGORY_HOBBIES_TOYS_AND_GAMES
     * @see CATEGORY_HOME_AND_GARDEN
     * @see CATEGORY_HOME_AUDIO
     * @see CATEGORY_HOME_DECOR
     * @see CATEGORY_HOME_ELECTRONICS
     * @see CATEGORY_HOSPITALS
     * @see CATEGORY_HOTELS_MOTELS_INNS_RESORTS
     * @see CATEGORY_HOUSEWARES
     * @see CATEGORY_HUMAN_PARTS_AND_REMAINS
     * @see CATEGORY_HUMOROUS_GIFTS_AND_NOVELTIES
     * @see CATEGORY_HUNTING
     * @see CATEGORY_IDS_LICENSES_AND_PASSPORTS
     * @see CATEGORY_ILLEGAL_DRUGS_AND_PARAPHERNALIA
     * @see CATEGORY_INDUSTRIAL
     * @see CATEGORY_INDUSTRIAL_AND_MANUFACTURING_SUPPLIES
     * @see CATEGORY_INSURANCE_AUTO_AND_HOME
     * @see CATEGORY_INSURANCE_DIRECT
     * @see CATEGORY_INSURANCE_LIFE_AND_ANNUITY
     * @see CATEGORY_INSURANCE_SALES_UNDERWRITING
     * @see CATEGORY_INSURANCE_UNDERWRITING_PREMIUMS
     * @see CATEGORY_INTERNET_AND_NETWORK_SERVICES
     * @see CATEGORY_INTRA_COMPANY_PURCHASES
     * @see CATEGORY_LABORATORIES_DENTAL_MEDICAL
     * @see CATEGORY_LANDSCAPING
     * @see CATEGORY_LANDSCAPING_AND_HORTICULTURAL_SERVICES
     * @see CATEGORY_LAUNDRY_CLEANING_SERVICES
     * @see CATEGORY_LEGAL
     * @see CATEGORY_LEGAL_SERVICES_AND_ATTORNEYS
     * @see CATEGORY_LOCAL_DELIVERY_SERVICE
     * @see CATEGORY_LOCKSMITH
     * @see CATEGORY_LODGING_AND_ACCOMMODATIONS
     * @see CATEGORY_LOTTERY_AND_CONTESTS
     * @see CATEGORY_LUGGAGE_AND_LEATHER_GOODS
     * @see CATEGORY_LUMBER_AND_BUILDING_MATERIALS
     * @see CATEGORY_MAGAZINES
     * @see CATEGORY_MAINTENANCE_AND_REPAIR_SERVICES
     * @see CATEGORY_MAKEUP_AND_COSMETICS
     * @see CATEGORY_MANUAL_CASH_DISBURSEMENTS
     * @see CATEGORY_MASSAGE_PARLORS
     * @see CATEGORY_MEDICAL
     * @see CATEGORY_MEDICAL_AND_PHARMACEUTICAL
     * @see CATEGORY_MEDICAL_CARE
     * @see CATEGORY_MEDICAL_EQUIPMENT_AND_SUPPLIES
     * @see CATEGORY_MEDICAL_SERVICES
     * @see CATEGORY_MEETING_PLANNERS
     * @see CATEGORY_MEMBERSHIP_CLUBS_AND_ORGANIZATIONS
     * @see CATEGORY_MEMBERSHIP_COUNTRY_CLUBS_GOLF
     * @see CATEGORY_MEMORABILIA
     * @see CATEGORY_MEN_AND_BOY_CLOTHING_AND_ACCESSORY_STORES
     * @see CATEGORY_MEN_CLOTHING
     * @see CATEGORY_MERCHANDISE
     * @see CATEGORY_METAPHYSICAL
     * @see CATEGORY_MILITARIA
     * @see CATEGORY_MILITARY_AND_CIVIL_SERVICE_UNIFORMS
     * @see CATEGORY_MISC__AUTOMOTIVE_AIRCRAFT_AND_FARM_EQUIPMENT_DEALERS
     * @see CATEGORY_MISC__GENERAL_MERCHANDISE
     * @see CATEGORY_MISCELLANEOUS_GENERAL_SERVICES
     * @see CATEGORY_MISCELLANEOUS_REPAIR_SHOPS_AND_RELATED_SERVICES
     * @see CATEGORY_MODEL_KITS
     * @see CATEGORY_MONEY_TRANSFER_MEMBER_FINANCIAL_INSTITUTION
     * @see CATEGORY_MONEY_TRANSFER_MERCHANT
     * @see CATEGORY_MOTION_PICTURE_THEATERS
     * @see CATEGORY_MOTOR_FREIGHT_CARRIERS_AND_TRUCKING
     * @see CATEGORY_MOTOR_HOME_AND_RECREATIONAL_VEHICLE_RENTAL
     * @see CATEGORY_MOTOR_HOMES_DEALERS
     * @see CATEGORY_MOTOR_VEHICLE_SUPPLIES_AND_NEW_PARTS
     * @see CATEGORY_MOTORCYCLE_DEALERS
     * @see CATEGORY_MOTORCYCLES
     * @see CATEGORY_MOVIE
     * @see CATEGORY_MOVIE_TICKETS
     * @see CATEGORY_MOVING_AND_STORAGE
     * @see CATEGORY_MULTI_LEVEL_MARKETING
     * @see CATEGORY_MUSIC_CDS_CASSETTES_AND_ALBUMS
     * @see CATEGORY_MUSIC_STORE_INSTRUMENTS_AND_SHEET_MUSIC
     * @see CATEGORY_NETWORKING
     * @see CATEGORY_NEW_AGE
     * @see CATEGORY_NEW_PARTS_AND_SUPPLIES_MOTOR_VEHICLE
     * @see CATEGORY_NEWS_DEALERS_AND_NEWSTANDS
     * @see CATEGORY_NON_DURABLE_GOODS
     * @see CATEGORY_NON_FICTION
     * @see CATEGORY_NON_PROFIT_POLITICAL_AND_RELIGION
     * @see CATEGORY_NONPROFIT
     * @see CATEGORY_NOVELTIES
     * @see CATEGORY_OEM_SOFTWARE
     * @see CATEGORY_OFFICE_SUPPLIES_AND_EQUIPMENT
     * @see CATEGORY_ONLINE_DATING
     * @see CATEGORY_ONLINE_GAMING
     * @see CATEGORY_ONLINE_GAMING_CURRENCY
     * @see CATEGORY_ONLINE_SERVICES
     * @see CATEGORY_OOUTBOUND_TELEMARKETING_MERCH
     * @see CATEGORY_OPHTHALMOLOGISTS_OPTOMETRIST
     * @see CATEGORY_OPTICIANS_AND_DISPENSING
     * @see CATEGORY_ORTHOPEDIC_GOODS_PROSTHETICS
     * @see CATEGORY_OSTEOPATHS
     * @see CATEGORY_OTHER
     * @see CATEGORY_PACKAGE_TOUR_OPERATORS
     * @see CATEGORY_PAINTBALL
     * @see CATEGORY_PAINTS_VARNISHES_AND_SUPPLIES
     * @see CATEGORY_PARKING_LOTS_AND_GARAGES
     * @see CATEGORY_PARTS_AND_ACCESSORIES
     * @see CATEGORY_PAWN_SHOPS
     * @see CATEGORY_PAYCHECK_LENDER_OR_CASH_ADVANCE
     * @see CATEGORY_PERIPHERALS
     * @see CATEGORY_PERSONALIZED_GIFTS
     * @see CATEGORY_PET_SHOPS_PET_FOOD_AND_SUPPLIES
     * @see CATEGORY_PETROLEUM_AND_PETROLEUM_PRODUCTS
     * @see CATEGORY_PETS_AND_ANIMALS
     * @see CATEGORY_PHOTOFINISHING_LABORATORIES_PHOTO_DEVELOPING
     * @see CATEGORY_PHOTOGRAPHIC_STUDIOS_PORTRAITS
     * @see CATEGORY_PHOTOGRAPHY
     * @see CATEGORY_PHYSICAL_GOOD
     * @see CATEGORY_PICTURE_VIDEO_PRODUCTION
     * @see CATEGORY_PIECE_GOODS_NOTIONS_AND_OTHER_DRY_GOODS
     * @see CATEGORY_PLANTS_AND_SEEDS
     * @see CATEGORY_PLUMBING_AND_HEATING_EQUIPMENTS_AND_SUPPLIES
     * @see CATEGORY_POLICE_RELATED_ITEMS
     * @see CATEGORY_POLITICAL_ORGANIZATIONS
     * @see CATEGORY_POSTAL_SERVICES_GOVERNMENT_ONLY
     * @see CATEGORY_POSTERS
     * @see CATEGORY_PREPAID_AND_STORED_VALUE_CARDS
     * @see CATEGORY_PRESCRIPTION_DRUGS
     * @see CATEGORY_PROMOTIONAL_ITEMS
     * @see CATEGORY_PUBLIC_WAREHOUSING_AND_STORAGE
     * @see CATEGORY_PUBLISHING_AND_PRINTING
     * @see CATEGORY_PUBLISHING_SERVICES
     * @see CATEGORY_RADAR_DECTORS
     * @see CATEGORY_RADIO_TELEVISION_AND_STEREO_REPAIR
     * @see CATEGORY_REAL_ESTATE
     * @see CATEGORY_REAL_ESTATE_AGENT
     * @see CATEGORY_REAL_ESTATE_AGENTS_AND_MANAGERS_RENTALS
     * @see CATEGORY_RELIGION_AND_SPIRITUALITY_FOR_PROFIT
     * @see CATEGORY_RELIGIOUS
     * @see CATEGORY_RELIGIOUS_ORGANIZATIONS
     * @see CATEGORY_REMITTANCE
     * @see CATEGORY_RENTAL_PROPERTY_MANAGEMENT
     * @see CATEGORY_RESIDENTIAL
     * @see CATEGORY_RETAIL
     * @see CATEGORY_RETAIL_FINE_JEWELRY_AND_WATCHES
     * @see CATEGORY_REUPHOLSTERY_AND_FURNITURE_REPAIR
     * @see CATEGORY_RINGS
     * @see CATEGORY_ROOFING_SIDING_SHEET_METAL
     * @see CATEGORY_RUGS_AND_CARPETS
     * @see CATEGORY_SCHOOLS_AND_COLLEGES
     * @see CATEGORY_SCIENCE_FICTION
     * @see CATEGORY_SCRAPBOOKING
     * @see CATEGORY_SCULPTURES
     * @see CATEGORY_SECURITIES_BROKERS_AND_DEALERS
     * @see CATEGORY_SECURITY_AND_SURVEILLANCE
     * @see CATEGORY_SECURITY_AND_SURVEILLANCE_EQUIPMENT
     * @see CATEGORY_SECURITY_BROKERS_AND_DEALERS
     * @see CATEGORY_SEMINARS
     * @see CATEGORY_SERVICE_STATIONS
     * @see CATEGORY_SERVICES
     * @see CATEGORY_SEWING_NEEDLEWORK_FABRIC_AND_PIECE_GOODS_STORES
     * @see CATEGORY_SHIPPING_AND_PACKING
     * @see CATEGORY_SHOE_REPAIR_HAT_CLEANING
     * @see CATEGORY_SHOE_STORES
     * @see CATEGORY_SHOES
     * @see CATEGORY_SNOWMOBILE_DEALERS
     * @see CATEGORY_SOFTWARE
     * @see CATEGORY_SPECIALTY_AND_MISC__FOOD_STORES
     * @see CATEGORY_SPECIALTY_CLEANING_POLISHING_AND_SANITATION_PREPARATIONS
     * @see CATEGORY_SPECIALTY_OR_RARE_PETS
     * @see CATEGORY_SPORT_GAMES_AND_TOYS
     * @see CATEGORY_SPORTING_AND_RECREATIONAL_CAMPS
     * @see CATEGORY_SPORTING_GOODS
     * @see CATEGORY_SPORTS_AND_OUTDOORS
     * @see CATEGORY_SPORTS_AND_RECREATION
     * @see CATEGORY_STAMP_AND_COIN
     * @see CATEGORY_STATIONARY_PRINTING_AND_WRITING_PAPER
     * @see CATEGORY_STENOGRAPHIC_AND_SECRETARIAL_SUPPORT_SERVICES
     * @see CATEGORY_STOCKS_BONDS_SECURITIES_AND_RELATED_CERTIFICATES
     * @see CATEGORY_STORED_VALUE_CARDS
     * @see CATEGORY_SUPPLIES
     * @see CATEGORY_SUPPLIES_AND_TOYS
     * @see CATEGORY_SURVEILLANCE_EQUIPMENT
     * @see CATEGORY_SWIMMING_POOLS_AND_SPAS
     * @see CATEGORY_SWIMMING_POOLS_SALES_SUPPLIES_SERVICES
     * @see CATEGORY_TAILORS_AND_ALTERATIONS
     * @see CATEGORY_TAX_PAYMENTS
     * @see CATEGORY_TAX_PAYMENTS_GOVERNMENT_AGENCIES
     * @see CATEGORY_TAXICABS_AND_LIMOUSINES
     * @see CATEGORY_TELECOMMUNICATION_SERVICES
     * @see CATEGORY_TELEPHONE_CARDS
     * @see CATEGORY_TELEPHONE_EQUIPMENT
     * @see CATEGORY_TELEPHONE_SERVICES
     * @see CATEGORY_THEATER
     * @see CATEGORY_TIRE_RETREADING_AND_REPAIR
     * @see CATEGORY_TOLL_OR_BRIDGE_FEES
     * @see CATEGORY_TOOLS_AND_EQUIPMENT
     * @see CATEGORY_TOURIST_ATTRACTIONS_AND_EXHIBITS
     * @see CATEGORY_TOWING_SERVICE
     * @see CATEGORY_TOYS_AND_GAMES
     * @see CATEGORY_TRADE_AND_VOCATIONAL_SCHOOLS
     * @see CATEGORY_TRADEMARK_INFRINGEMENT
     * @see CATEGORY_TRAILER_PARKS_AND_CAMPGROUNDS
     * @see CATEGORY_TRAINING_SERVICES
     * @see CATEGORY_TRANSPORTATION_SERVICES
     * @see CATEGORY_TRAVEL
     * @see CATEGORY_TRUCK_AND_UTILITY_TRAILER_RENTALS
     * @see CATEGORY_TRUCK_STOP
     * @see CATEGORY_TYPESETTING_PLATE_MAKING_AND_RELATED_SERVICES
     * @see CATEGORY_USED_MERCHANDISE_AND_SECONDHAND_STORES
     * @see CATEGORY_USED_PARTS_MOTOR_VEHICLE
     * @see CATEGORY_UTILITIES
     * @see CATEGORY_UTILITIES_ELECTRIC_GAS_WATER_SANITARY
     * @see CATEGORY_VARIETY_STORES
     * @see CATEGORY_VEHICLE_SALES
     * @see CATEGORY_VEHICLE_SERVICE_AND_ACCESSORIES
     * @see CATEGORY_VIDEO_EQUIPMENT
     * @see CATEGORY_VIDEO_GAME_ARCADES_ESTABLISH
     * @see CATEGORY_VIDEO_GAMES_AND_SYSTEMS
     * @see CATEGORY_VIDEO_TAPE_RENTAL_STORES
     * @see CATEGORY_VINTAGE_AND_COLLECTIBLE_VEHICLES
     * @see CATEGORY_VINTAGE_AND_COLLECTIBLES
     * @see CATEGORY_VITAMINS_AND_SUPPLEMENTS
     * @see CATEGORY_VOCATIONAL_AND_TRADE_SCHOOLS
     * @see CATEGORY_WATCH_CLOCK_AND_JEWELRY_REPAIR
     * @see CATEGORY_WEB_HOSTING_AND_DESIGN
     * @see CATEGORY_WELDING_REPAIR
     * @see CATEGORY_WHOLESALE_CLUBS
     * @see CATEGORY_WHOLESALE_FLORIST_SUPPLIERS
     * @see CATEGORY_WHOLESALE_PRESCRIPTION_DRUGS
     * @see CATEGORY_WILDLIFE_PRODUCTS
     * @see CATEGORY_WIRE_TRANSFER
     * @see CATEGORY_WIRE_TRANSFER_AND_MONEY_ORDER
     * @see CATEGORY_WOMEN_ACCESSORY_SPECIALITY
     * @see CATEGORY_WOMEN_CLOTHING
     * @var string | null
     * minLength: 4
     * maxLength: 256
     */
    public $category;

    /**
     * The image URL for the product.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 2000
     */
    public $image_url;

    /**
     * The home page URL for the product.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 2000
     */
    public $home_url;

    /**
     * The date and time, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6).
     * Seconds are required while fractional seconds are optional.<blockquote><strong>Note:</strong> The regular
     * expression provides guidance but does not reject all invalid dates.</blockquote>
     *
     * @var string | null
     * minLength: 20
     * maxLength: 64
     */
    public $create_time;

    /**
     * The date and time, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6).
     * Seconds are required while fractional seconds are optional.<blockquote><strong>Note:</strong> The regular
     * expression provides guidance but does not reject all invalid dates.</blockquote>
     *
     * @var string | null
     * minLength: 20
     * maxLength: 64
     */
    public $update_time;

    /**
     * An array of request-related [HATEOAS links](/docs/api/overview/#hateoas-links).
     *
     * @var array | null
     */
    public $links;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->id) || Assert::minLength(
            $this->id,
            6,
            "id in Product must have minlength of 6 $within"
        );
        !isset($this->id) || Assert::maxLength(
            $this->id,
            50,
            "id in Product must have maxlength of 50 $within"
        );
        !isset($this->name) || Assert::minLength(
            $this->name,
            1,
            "name in Product must have minlength of 1 $within"
        );
        !isset($this->name) || Assert::maxLength(
            $this->name,
            127,
            "name in Product must have maxlength of 127 $within"
        );
        !isset($this->description) || Assert::minLength(
            $this->description,
            1,
            "description in Product must have minlength of 1 $within"
        );
        !isset($this->description) || Assert::maxLength(
            $this->description,
            256,
            "description in Product must have maxlength of 256 $within"
        );
        !isset($this->type) || Assert::minLength(
            $this->type,
            1,
            "type in Product must have minlength of 1 $within"
        );
        !isset($this->type) || Assert::maxLength(
            $this->type,
            24,
            "type in Product must have maxlength of 24 $within"
        );
        !isset($this->category) || Assert::minLength(
            $this->category,
            4,
            "category in Product must have minlength of 4 $within"
        );
        !isset($this->category) || Assert::maxLength(
            $this->category,
            256,
            "category in Product must have maxlength of 256 $within"
        );
        !isset($this->image_url) || Assert::minLength(
            $this->image_url,
            1,
            "image_url in Product must have minlength of 1 $within"
        );
        !isset($this->image_url) || Assert::maxLength(
            $this->image_url,
            2000,
            "image_url in Product must have maxlength of 2000 $within"
        );
        !isset($this->home_url) || Assert::minLength(
            $this->home_url,
            1,
            "home_url in Product must have minlength of 1 $within"
        );
        !isset($this->home_url) || Assert::maxLength(
            $this->home_url,
            2000,
            "home_url in Product must have maxlength of 2000 $within"
        );
        !isset($this->create_time) || Assert::minLength(
            $this->create_time,
            20,
            "create_time in Product must have minlength of 20 $within"
        );
        !isset($this->create_time) || Assert::maxLength(
            $this->create_time,
            64,
            "create_time in Product must have maxlength of 64 $within"
        );
        !isset($this->update_time) || Assert::minLength(
            $this->update_time,
            20,
            "update_time in Product must have minlength of 20 $within"
        );
        !isset($this->update_time) || Assert::maxLength(
            $this->update_time,
            64,
            "update_time in Product must have maxlength of 64 $within"
        );
        !isset($this->links) || Assert::isArray(
            $this->links,
            "links in Product must be array $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
        if (isset($data['type'])) {
            $this->type = $data['type'];
        }
        if (isset($data['category'])) {
            $this->category = $data['category'];
        }
        if (isset($data['image_url'])) {
            $this->image_url = $data['image_url'];
        }
        if (isset($data['home_url'])) {
            $this->home_url = $data['home_url'];
        }
        if (isset($data['create_time'])) {
            $this->create_time = $data['create_time'];
        }
        if (isset($data['update_time'])) {
            $this->update_time = $data['update_time'];
        }
        if (isset($data['links'])) {
            $this->links = [];
            foreach ($data['links'] as $item) {
                $this->links[] = $item;
            }
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
