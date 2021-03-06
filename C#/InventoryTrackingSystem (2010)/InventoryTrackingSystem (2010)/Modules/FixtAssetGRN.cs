﻿using System;
using System.Collections.Generic;
using System.Text;
using dbexlib;

namespace InventoryTrackingSystem
{
    class FixtAssetGRN
    {
        ItemCategories _itemCategory = new ItemCategories();
        Location _location = new Location();
        Companies _companies = new Companies();
        Employee _emnployee = new Employee();
        static string TABLE = "its_stock_trans";

        public string getCurrentCode()
        {
            string qry = @" SELECT
                                  IFNULL(MAX(trans_code),'null') AS code
                            FROM its_stock_trans
                            WHERE stock_code='FA'
                                    AND trans_type='GRN' AND `from`='USR'";
            int a;
            string code = "";

            var res = DB.Select(qry);

            code = ((res[0])["code"] == "null") ? "0" : res[0]["code"];

            a = Convert.ToInt32(code) + 1;

            code = a.ToString();
            if (a < 10)
            {
                code = "0000" + code;
            }
            else if (a >= 10 && a < 100)
            {
                code = "000" + code;
            }
            else if (a >= 100 && a < 1000)
            {
                code = "00" + code;
            }
            else if (a >= 1000 && a < 10000)
            {
                code = "0" + code;
            }

            return code;
        }

        #region Autocomlete
        public string GetItemCat(string code)
        {
            return _itemCategory.Get(code, "FA");
        }
        public string GetLocation(string code)
        {
            return _location.exLocation(code);
        }
        public string GetCompanies(string code)
        {
            return _companies.CompaniesName(code);
        }
        public string GetItemName(string code)
        {
            string qry =@"SELECT
                              item_name
                            FROM its_stock_trans
                            WHERE stock_code = 'FA'
                                AND item_name LIKE " + DB.Escape(code + "%") + "GROUP BY item_name";
           // Log.write(qry);
            return DB.GetToAutocomplete(qry);
        }

        public string GetEmp(string code)
        {
            string qry = @"SELECT
                              CONCAT(emp_name,'-',emp_code) AS `name`
                            FROM its_employee
                            WHERE emp_name LIKE" + DB.Escape(code + "%");
            return DB.GetToAutocomplete(qry);
        }
        public string GetItemNames(string code)
        {
            return _emnployee.exEmp(code);
        }
        #endregion

        public string getSerial(string key)
        {
            string qry = @" SELECT
                                  IFNULL(MAX(serial_code),'null') AS code
                            FROM its_stock_trans
                            WHERE stock_code='FA'
                                    AND trans_type='GRN' AND `from`='USR' AND item_cat=" + DB.Escape(key);
            int a;
            string code = "";

            var res = DB.Select(qry);

            code = ((res[0])["code"] == "null") ? "0" : res[0]["code"];

            a = Convert.ToInt32(code) + 1;

            code = a.ToString();
            if (a < 10)
            {
                code = "0000" + code;
            }
            else if (a >= 10 && a < 100)
            {
                code = "000" + code;
            }
            else if (a >= 100 && a < 1000)
            {
                code = "00" + code;
            }
            else if (a >= 1000 && a < 10000)
            {
                code = "0" + code;
            }

            return code;
     
        }

        public int Save(FixtAssetData data)
        {
            string qry = "INSERT INTO " + TABLE
                                     + " (`trans_date`,`trans_code`,`serial_code`,`ref_no`,`item_cat`,`item_name`,`value`,`location_code`,`company_code`,`current_user`,`desc`,`remark`,`stock_code`,`trans_type`,`from`,`is_available`)VALUES ("
                                     + DB.Escape(data.Date) + ","
                                     + DB.Escape(data.TransCode) + ","
                                     + DB.Escape(data.Serial) + ","
                                     + DB.Escape(data.RefNo) + ","
                                     + DB.Escape(data.ItemCategory) + ","
                                     + DB.Escape(data.Name) + ","
                                     + DB.Escape(data.Price) + ","
                                     + DB.Escape(data.Location) + ","
                                     + DB.Escape(data.Companies) + ","
                                     + DB.Escape(data.Current_User) + ","
                                     + DB.Escape(data.Description) + ","
                                     + DB.Escape(data.Remark) + ","
                                     + "'FA','GRN','USR','Y');";
            //Log.write(qry);
            return DB.NonQuery(qry);
        }

        public FixtAssetData getDataBy(string code,int var1)
        {
            FixtAssetData fld = new FixtAssetData();
            string qry = @"SELECT
                                 `its_stock_trans`.`trans_code`
                                , `its_stock_trans`.`item_cat`
                                , `its_stock_trans`.`item_name`
                                , `its_stock_trans`.`value`
                                , `its_stock_trans`.`serial_code`
                                , `its_stock_trans`.`desc` 
                                , CONCAT(`its_employee`.`emp_name`,'-',`its_employee`.`emp_code`) AS `current_user`
                                , `its_stock_trans`.`trans_date`
                                , `its_stock_trans`.`remark`
                                , `its_stock_trans`.`ref_no`
                                , `its_stock_trans`.`company_code`
                                , `its_stock_trans`.`location_code`
                            FROM
                                `its_stock_trans`
                                INNER JOIN `hec_its`.`its_employee` 
                                    ON (`its_stock_trans`.`current_user` = `its_employee`.`emp_code`)";

            switch (var1)
            {
                case 1:
                  qry +="WHERE (`its_stock_trans`.`trans_code` =" + DB.Escape(code)
                           + "AND `its_stock_trans`.`stock_code` ='FA' AND `its_stock_trans`.`trans_type` ='GRN' AND `from`='USR')";
                  break;
                case 2:
                  qry += "WHERE (`its_stock_trans`.`ref_no` =" + DB.Escape(code)
                           + "AND `its_stock_trans`.`stock_code` ='FA' AND `its_stock_trans`.`trans_type` ='GRN' AND `from`='USR')";
                  break;

            }
           
            var res = DB.Select(qry);
            if (res.Count > 0)
            {
                foreach (var data in res)
                {
                    fld.TransCode = data["trans_code"];
                    fld.Date = data["trans_date"];
                    fld.RefNo = data["ref_no"];
                    fld.ItemCategory = data["item_cat"];
                    fld.Serial =data["item_cat"]+"-"+data["serial_code"];
                    fld.Companies = data["company_code"];
                    fld.Name = data["item_name"];
                    fld.Price = data["value"];
                    fld.Location = data["location_code"];
                    fld.Current_User = data["current_user"];
                    fld.Description = data["desc"];
                    fld.Remark = data["remark"];

                }

                return fld;
            }
            else
            {
                return null;
            }
        }

        public FixtAssetData getDataByAll(string code, int var1)
        {
            FixtAssetData fld = new FixtAssetData();
            string qry = @"SELECT
                                 `its_stock_trans`.`trans_code`
                                , `its_stock_trans`.`item_cat`
                                , `its_stock_trans`.`item_name`
                                , `its_stock_trans`.`value`
                                , `its_stock_trans`.`serial_code`
                                , `its_stock_trans`.`desc` 
                                , CONCAT(`its_employee`.`emp_name`,'-',`its_employee`.`emp_code`) AS `current_user`
                                , `its_stock_trans`.`trans_date`
                                , `its_stock_trans`.`remark`
                                , `its_stock_trans`.`ref_no`
                                , `its_stock_trans`.`company_code`
                                , `its_stock_trans`.`location_code`
                            FROM
                                `its_stock_trans`
                                INNER JOIN `hec_its`.`its_employee` 
                                    ON (`its_stock_trans`.`current_user` = `its_employee`.`emp_code`)";

            switch (var1)
            {
                case 1:
                    qry += "WHERE (`its_stock_trans`.`trans_code` =" + DB.Escape(code)
                             + "AND `its_stock_trans`.`stock_code` ='FA' AND `its_stock_trans`.`trans_type` ='GRN' AND (`from`='USR' OR `from`='GTN'))";
                    break;
                case 2:
                    qry += "WHERE (`its_stock_trans`.`ref_no` =" + DB.Escape(code)
                             + "AND `its_stock_trans`.`stock_code` ='FA' AND `its_stock_trans`.`trans_type` ='GRN' AND (`from`='USR' OR `from`='GTN'))";
                    break;

            }

            var res = DB.Select(qry);
            if (res.Count > 0)
            {
                foreach (var data in res)
                {
                    fld.TransCode = data["trans_code"];
                    fld.Date = data["trans_date"];
                    fld.RefNo = data["ref_no"];
                    fld.ItemCategory = data["item_cat"];
                    fld.Serial = data["item_cat"] + "-" + data["serial_code"];
                    fld.Companies = data["company_code"];
                    fld.Name = data["item_name"];
                    fld.Price = data["value"];
                    fld.Location = data["location_code"];
                    fld.Current_User = data["current_user"];
                    fld.Description = data["desc"];
                    fld.Remark = data["remark"];

                }

                return fld;
            }
            else
            {
                return null;
            }
        }

        public int Delete(string code)
        {
            string qry = "DELETE FROM its_stock_trans WHERE trans_code=" + DB.Escape(code) + " AND trans_type='GRN' AND stock_code='FA' AND `from`='USR'";
            return DB.NonQuery(qry);
        }

        public int Update(FixtAssetData data)
        {
            string qry = "UPDATE its_stock_trans SET `trans_date` ="
                                      + DB.Escape(data.Date) + ",`ref_no` = "
                                      + DB.Escape(data.RefNo) + ",`item_cat` = "
                                      + DB.Escape(data.ItemCategory) + ",`item_name` = "
                                      + DB.Escape(data.Name) + ",`serial_code` ="
                                      + DB.Escape(data.Serial) + ",`value` = "
                                      + DB.Escape(data.Price) + ",`location_code` ="
                                      + DB.Escape(data.Location) + ",`company_code` ="
                                      + DB.Escape(data.Companies) + ",`current_user` ="
                                      + DB.Escape(data.Current_User) + ",`desc` ="
                                      + DB.Escape(data.Description) + ",`remark` = "
                                      + DB.Escape(data.Remark) + " WHERE trans_code ="
                                      + DB.Escape(data.TransCode) + " AND stock_code = 'FA' AND trans_type = 'GRN' AND `from`='USR'";

            //Log.write(qry);
            return DB.Update(qry);

        }


    }
}
