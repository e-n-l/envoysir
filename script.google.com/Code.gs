/**
 * Adds a custom menu to the active spreadsheet, containing a single menu item
 * for invoking the readRows() function specified above.
 * The onOpen() function, when defined, is automatically invoked whenever the
 * spreadsheet is opened.
 * For more information on using the Spreadsheet API, see
 * https://developers.google.com/apps-script/service_spreadsheet
 */
function onOpen() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var entries = [
    {name : "Grab Data",
    functionName : "basic"
  },
    {name : "Grab Data (Box Compacting)",
    functionName : "box_com"
  },
    {name : "Grab Data (Compacting, no 1002s)",
    functionName : "com_1002"
  },
    {name : "Clear Cached Order",
    functionName : "force_reload"
  },
    {name : "Clear Data",
    functionName : "clearStuffUp"
  }];
  ss.addMenu("Lookup Order Data", entries);
}


// This function is for putting data directly into the calculator, rather than the input page.
// Here's where the magic happens...
function basic(){
  CacheService.getPrivateCache().put("format", "&f=calc");
  cutcoIData();
}
function box_com(){
  CacheService.getPrivateCache().put("format", "&f=boxCalc");
  cutcoIData();
}
function com_1002(){
  CacheService.getPrivateCache().put("format", "&f=boxCalc&1002=true");
  cutcoIData();
}
function force_reload(){
  CacheService.getPrivateCache().put("format", "&r=1");
  cutcoIData(true);
}

function cutcoIData(silent){
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var controlvalues = ss.getSheetByName("Control").getRange("B1:B2").getValues();
  
  var order = controlvalues[1][0].toString().trim();
  if(order == "") {
    return "Order Number required";
  }
  CacheService.getPrivateCache().put("order", "o="+order);
  
  var lname = controlvalues[0][0].toString().trim();
  
  if(lname == "") {
    return "Last Name required";
  }
  CacheService.getPrivateCache().put("lname", "&l="+lname);

  Logger.log(CacheService.getPrivateCache().get("order")
  +CacheService.getPrivateCache().get("lname")
  +CacheService.getPrivateCache().get("format"));
  
  var resp = UrlFetchApp.fetch(constructScrapeURL());
  Logger.log(resp.getContentText());
  
  // parse the response as a CSV
  var csvContent = parseCsvResponse_(resp.getContentText());
  
  // break the response up into what we need
  var qty_sku_color = [];
  var eng_bonus = [];
  var otherInfo = [[],[],[""],[],[]];
  for (row = 0, len = csvContent.length; row < len; row++) {
    if(row<12){
      qty_sku_color[row]=[];
      eng_bonus[row]=[];
      for(col = 0, len2 = csvContent[row].length; col < len2; col++){
        // first chunk of item info
        if(col<=2)
          qty_sku_color[row].push(csvContent[row][col])
        // 2nd chunk of item info
        else if (col <= 4)
          eng_bonus[row].push(csvContent[row][col]);
      }
    }
    // grab various other bits of data to insert...
    else if(csvContent[row][0]=="Billed To"){
      otherInfo[0][0] = csvContent[row][1];
      otherInfo[1][0] = csvContent[row][csvContent[row].length-2];
    }
    else if(csvContent[row][0]=="Order Date")
      otherInfo[3][0] = csvContent[row][1];
    else if(csvContent[row][0]=="Rep Name")
      otherInfo[4][0] = csvContent[row][1];
  }
  
  
  // clear the fillable areas in the sheet.
  clearStuffUp();
  if(!silent){
    // set the values in the sheet (as efficiently as we know how)
    ss.getSheetByName("CSV Import").getRange(
      1, 1,
      csvContent.length, /* rows */
      csvContent[0].length /* columns */).setValues(csvContent);
    if(qty_sku_color.length==12){
      ss.getSheetByName("Control").getRange("B4:B8").setValues(otherInfo);
    }
  }
};


// clear the fillable areas in the sheet.
function clearStuffUp(){
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  ss.getSheetByName("CSV Import").clearContents().clearFormats();
  var sheet = ss.getSheetByName("Control");
  if(sheet){
    sheet.getRange("B4:B8").clearContent();
  }
};

function constructScrapeURL(){
  return "http://qgs.biz/resources/cutco.scrape.php?"
  +CacheService.getPrivateCache().get("order")
  +CacheService.getPrivateCache().get("lname")
  +CacheService.getPrivateCache().get("format");
};
