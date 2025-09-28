// To parse this JSON data, do
//
//     final cartTools = cartToolsFromJson(jsonString);

import 'dart:convert';

CartTools cartToolsFromJson(String str) => CartTools.fromJson(json.decode(str));

String cartToolsToJson(CartTools data) => json.encode(data.toJson());

class CartTools {
    String courseSellingTax;
    String currencyPosition;
    String currencySymbol;

    CartTools({
        required this.courseSellingTax,
        required this.currencyPosition,
        required this.currencySymbol,
    });

    factory CartTools.fromJson(Map<String, dynamic> json) => CartTools(
        courseSellingTax: json["course_selling_tax"],
        currencyPosition: json["currency_position"],
        currencySymbol: json["currency_symbol"],
    );

    Map<String, dynamic> toJson() => {
        "course_selling_tax": courseSellingTax,
        "currency_position": currencyPosition,
        "currency_symbol": currencySymbol,
    };
}
