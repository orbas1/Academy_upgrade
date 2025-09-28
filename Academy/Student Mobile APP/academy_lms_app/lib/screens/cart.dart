// ignore_for_file: avoid_print

import 'dart:async';
import 'dart:convert';

import 'package:academy_lms_app/models/cart_tools_model.dart';
import 'package:academy_lms_app/models/course.dart';
import 'package:academy_lms_app/providers/courses.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../constants.dart';
import '../widgets/common_functions.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  CartTools? _cartTools;
  bool isLoading = false;

  double calculateSubtotal(List<Course> courses) {
    double subtotal = 0.00;

    for (var course in courses) {
      String priceText = course.price_cart.toString();

      try {
        double price = double.parse(priceText);
        subtotal += price;
      } catch (e) {
        print(
            'Invalid price format for course: ${course.title}, price: ${course.price}');
      }
    }
    return subtotal;
  }

  double calculateTax(double subtotal, String taxRateString) {
    try {
      double taxRate = double.parse(taxRateString) / 100;
      return subtotal * taxRate;
    } catch (e) {
      print('Invalid tax rate format: $taxRateString');
      return 0.00;
    }
  }

  Future<void> fetchUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final authToken = prefs.getString('access_token') ?? '';

    var url = '$baseUrl/api/payment';
    try {
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $authToken',
        },
      );

      print('Response body: ${response.body}');

      if (response.statusCode == 200) {
        if (await canLaunchUrl(Uri.parse(url))) {
          await launchUrl(
            Uri.parse(url),
            mode: LaunchMode.externalApplication,
          );
        } else {
          throw 'Could not launch $url';
        }
      } else {
        print('Error: ${response.statusCode}');
      }
    } catch (e) {
      print(e.toString());
    }
  }

  //  Timer? _timer;

  @override
  void initState() {
    super.initState();
    fetchCartTools();
    // _timer = Timer.periodic(Duration(milliseconds: 5), (timer) {
    //   fetchCartTools();
    // });
  }

  // @override
  // void dispose() {
  //   _timer?.cancel();
  //   super.dispose();
  // }

  Future<void> fetchCartTools() async {
    setState(() {
      isLoading = true;
    });
    final prefs = await SharedPreferences.getInstance();
    final authToken = (prefs.getString('access_token') ?? '');
    var url = '$baseUrl/api/cart_tools';

    try {
      final response = await http.get(Uri.parse(url), headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      });

      if (response.statusCode == 200) {
        final extractedData = json.decode(response.body);

        if (extractedData != null) {
          _cartTools = CartTools.fromJson(extractedData);
        }
      } else {
        print('Failed to load cart tools. Status code: ${response.statusCode}');
      }
    } catch (error) {
      rethrow;
    }
    setState(() {
      isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.of(context).size.height -
        MediaQuery.of(context).padding.top -
        kToolbarHeight -
        50;
    return Scaffold(
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: RefreshIndicator(
          onRefresh: fetchCartTools,
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              child: FutureBuilder(
                  future: Provider.of<Courses>(context, listen: false)
                      .fetchCartlist(),
                  builder: (ctx, dataSnapshot) {
                    if (dataSnapshot.connectionState ==
                        ConnectionState.waiting) {
                      return SizedBox(
                        height: height,
                        child: const Center(
                            child: CupertinoActivityIndicator(
                          color: kDefaultColor,
                        )),
                      );
                    } else {
                      if (dataSnapshot.error != null) {
                        return Center(
                          child: Text('Error Occured'),
                          // child: Text(dataSnapshot.error.toString()),
                        );
                      } else {
                        return Consumer<Courses>(
                            builder: (context, cartData, child) {
                          double subtotal = calculateSubtotal(cartData.items);
                          double tax = calculateTax(
                              subtotal, _cartTools!.courseSellingTax);
                          double total = subtotal + tax;
                          return Column(
                            children: [
                              Container(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 10),
                                child: ListView.builder(
                                    shrinkWrap: true,
                                    physics:
                                        const NeverScrollableScrollPhysics(),
                                    itemCount: cartData.items.length,
                                    itemBuilder: (ctx, index) {
                                      return Container(
                                        decoration: BoxDecoration(
                                          boxShadow: [
                                            BoxShadow(
                                              color: kBackButtonBorderColor
                                                  .withOpacity(0.05),
                                              blurRadius: 10,
                                              offset: const Offset(0, 0),
                                            ),
                                          ],
                                        ),
                                        child: Padding(
                                          padding: const EdgeInsets.only(
                                              bottom: 7.0),
                                          child: Card(
                                            color: Colors.white,
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(16),
                                            ),
                                            elevation: 0,
                                            child: Row(
                                              children: [
                                                Expanded(
                                                  flex: 3,
                                                  child: Padding(
                                                    padding:
                                                        const EdgeInsets.all(
                                                            10.0),
                                                    child: ClipRRect(
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              8),
                                                      child: Image.network(
                                                        cartData.items[index]
                                                            .thumbnail
                                                            .toString(),
                                                        width: 130,
                                                        height: 114,
                                                        fit: BoxFit.cover,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                Expanded(
                                                  flex: 4,
                                                  child: Column(
                                                    crossAxisAlignment:
                                                        CrossAxisAlignment
                                                            .start,
                                                    children: [
                                                      // Text(cartData.items[index].price_cart.toString()),
                                                      Padding(
                                                        padding:
                                                            const EdgeInsets
                                                                .symmetric(
                                                                horizontal: 5.0,
                                                                vertical: 5),
                                                        child: Row(
                                                          children: [
                                                            Expanded(
                                                              flex: 1,
                                                              child: Text(
                                                                cartData
                                                                    .items[
                                                                        index]
                                                                    .title
                                                                    .toString(),
                                                                maxLines: 2,
                                                                overflow:
                                                                    TextOverflow
                                                                        .ellipsis,
                                                                style:
                                                                    const TextStyle(
                                                                  fontSize: 16,
                                                                  fontWeight:
                                                                      FontWeight
                                                                          .w500,
                                                                ),
                                                              ),
                                                            ),
                                                            IconButton(
                                                              padding:
                                                                  EdgeInsets
                                                                      .zero,
                                                              visualDensity:
                                                                  VisualDensity
                                                                      .compact,
                                                              onPressed: () {
                                                                showDialog(
                                                                  context:
                                                                      context,
                                                                  builder: (BuildContext
                                                                          context) =>
                                                                      AlertDialog(
                                                                    title: const Text(
                                                                        'Notifying'),
                                                                    content:
                                                                        const Column(
                                                                      mainAxisSize:
                                                                          MainAxisSize
                                                                              .min,
                                                                      crossAxisAlignment:
                                                                          CrossAxisAlignment
                                                                              .start,
                                                                      children: <Widget>[
                                                                        Text(
                                                                            'Do you wish to remove this course?'),
                                                                      ],
                                                                    ),
                                                                    actions: <Widget>[
                                                                      MaterialButton(
                                                                        onPressed:
                                                                            () {
                                                                          Navigator.of(context)
                                                                              .pop();
                                                                        },
                                                                        textColor:
                                                                            Theme.of(context).primaryColor,
                                                                        child:
                                                                            const Text(
                                                                          'No',
                                                                          style:
                                                                              TextStyle(
                                                                            color:
                                                                                Colors.red,
                                                                          ),
                                                                        ),
                                                                      ),
                                                                      MaterialButton(
                                                                        onPressed:
                                                                            () {
                                                                          Navigator.of(context)
                                                                              .pop();
                                                                          CommonFunctions.showSuccessToast(
                                                                              'Removed from cart');
                                                                          Provider.of<Courses>(context, listen: false).toggleCart(
                                                                              cartData.items[index].id!,
                                                                              true);
                                                                        },
                                                                        textColor:
                                                                            Theme.of(context).primaryColor,
                                                                        child:
                                                                            const Text(
                                                                          'Yes',
                                                                          style:
                                                                              TextStyle(
                                                                            color:
                                                                                Colors.green,
                                                                          ),
                                                                        ),
                                                                      ),
                                                                    ],
                                                                  ),
                                                                );
                                                              },
                                                              icon: const Icon(
                                                                Icons
                                                                    .delete_outline_rounded,
                                                                color:
                                                                    kGreyLightColor,
                                                              ),
                                                            ),
                                                          ],
                                                        ),
                                                      ),
                                                      Row(
                                                        mainAxisAlignment:
                                                            MainAxisAlignment
                                                                .start,
                                                        children: [
                                                          Icon(
                                                            Icons.star,
                                                            color: kStarColor,
                                                            size: 18,
                                                          ),
                                                          Text(
                                                            " ${cartData.items[index].average_rating}",
                                                            style: TextStyle(
                                                              fontSize: 12,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .w400,
                                                              color:
                                                                  kGreyLightColor,
                                                            ),
                                                          ),
                                                          Text(
                                                            ' (${cartData.items[index].average_rating} Reviews)',
                                                            style: TextStyle(
                                                              fontSize: 12,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .w400,
                                                              color:
                                                                  kGreyLightColor,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                      const SizedBox(
                                                          height: 10),
                                                      Align(
                                                        alignment: Alignment
                                                            .bottomRight,
                                                        child: Padding(
                                                          padding:
                                                              const EdgeInsets
                                                                  .only(
                                                                  right: 10.0),
                                                          child: Text(
                                                            cartData
                                                                .items[index]
                                                                .price
                                                                .toString(),
                                                            style:
                                                                const TextStyle(
                                                              fontSize: 22,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .w500,
                                                            ),
                                                          ),
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ),
                                      );
                                    }),
                              ),
                              cartData.items.isEmpty
                                  ? Text("No Cart item is added now")
                                  : isLoading
                                      ? CircularProgressIndicator()
                                      : SizedBox(
                                          child: Padding(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 5.0),
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.end,
                                              children: [
                                                Row(
                                                  children: [
                                                    Text(
                                                      'Sub Total',
                                                      style: TextStyle(
                                                        fontSize: 18,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                      ),
                                                    ),
                                                    const Spacer(),
                                                    Text(
                                                      () {
                                                        switch (_cartTools!
                                                            .currencyPosition) {
                                                          case "right":
                                                            return '${subtotal.toStringAsFixed(2)}${_cartTools!.currencySymbol}';
                                                          case "left":
                                                            return '${_cartTools!.currencySymbol}${subtotal.toStringAsFixed(2)}';
                                                          case "right-space":
                                                            return '${subtotal.toStringAsFixed(2)} ${_cartTools!.currencySymbol}';
                                                          case "left-space":
                                                            return '${_cartTools!.currencySymbol} ${subtotal.toStringAsFixed(2)}';
                                                          default:
                                                            return '${_cartTools!.currencySymbol}${subtotal.toStringAsFixed(2)}';
                                                        }
                                                      }(),
                                                      style: const TextStyle(
                                                        fontSize: 20,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                        color: kDefaultColor,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                                SizedBox(
                                                  height: 10,
                                                ),
                                                Row(
                                                  children: [
                                                    Text(
                                                      'Tax (${_cartTools!.courseSellingTax}%)',
                                                      style: TextStyle(
                                                        fontSize: 18,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                      ),
                                                    ),
                                                    const Spacer(),
                                                    Text(
                                                      () {
                                                        switch (_cartTools!
                                                            .currencyPosition) {
                                                          case "right":
                                                            return '+ ${tax.toStringAsFixed(2)}${_cartTools!.currencySymbol}';
                                                          case "left":
                                                            return '+ ${_cartTools!.currencySymbol}${tax.toStringAsFixed(2)}';
                                                          case "right-space":
                                                            return '+ ${tax.toStringAsFixed(2)} ${_cartTools!.currencySymbol}';
                                                          case "left-space":
                                                            return '+ ${_cartTools!.currencySymbol} ${tax.toStringAsFixed(2)}';
                                                          default:
                                                            return '+ ${_cartTools!.currencySymbol}${tax.toStringAsFixed(2)}';
                                                        }
                                                      }(),
                                                      style: const TextStyle(
                                                        fontSize: 20,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                        color: kDefaultColor,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                                SizedBox(
                                                  height: 20,
                                                ),
                                                Row(
                                                  children: [
                                                    Text(
                                                      'Total',
                                                      style: TextStyle(
                                                        fontSize: 20,
                                                        fontWeight:
                                                            FontWeight.w600,
                                                      ),
                                                    ),
                                                    const Spacer(),
                                                    Text(
                                                      () {
                                                        switch (_cartTools!
                                                            .currencyPosition) {
                                                          case "right":
                                                            return '${total.toStringAsFixed(2)}${_cartTools!.currencySymbol}';
                                                          case "left":
                                                            return '${_cartTools!.currencySymbol}${total.toStringAsFixed(2)}';
                                                          case "right-space":
                                                            return '${total.toStringAsFixed(2)} ${_cartTools!.currencySymbol}';
                                                          case "left-space":
                                                            return '${_cartTools!.currencySymbol} ${total.toStringAsFixed(2)}';
                                                          default:
                                                            return '${_cartTools!.currencySymbol}${total.toStringAsFixed(2)}';
                                                        }
                                                      }(),
                                                      style: const TextStyle(
                                                        fontSize: 22,
                                                        fontWeight:
                                                            FontWeight.w600,
                                                        color: kBlackColor,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                                SizedBox(
                                                  height: 10,
                                                ),
                                                Container(
                                                  decoration: BoxDecoration(
                                                    boxShadow: [
                                                      BoxShadow(
                                                        color: kDefaultColor
                                                            .withOpacity(0.1),
                                                        blurRadius: 20,
                                                        offset:
                                                            const Offset(-5, 0),
                                                      ),
                                                    ],
                                                  ),
                                                  child: MaterialButton(
                                                    elevation: 0,
                                                    padding: const EdgeInsets
                                                        .symmetric(
                                                        horizontal: 10),
                                                    onPressed: () async {
                                                      final prefs =
                                                          await SharedPreferences
                                                              .getInstance();
                                                      final emailPre = prefs
                                                          .getString('email');
                                                      final passwordPre =
                                                          prefs.getString(
                                                              'password');
                                                      var email = emailPre;
                                                      var password =
                                                          passwordPre;
                                                      print(email);
                                                      print(password);
                                                      // var email = "student@example.com";
                                                      // var password = "12345678";
                                                      DateTime currentDateTime =
                                                          DateTime.now();
                                                      int currentTimestamp =
                                                          (currentDateTime
                                                                      .millisecondsSinceEpoch /
                                                                  1000)
                                                              .floor();

                                                      String authToken =
                                                          'Basic ${base64Encode(utf8.encode('$email:$password:$currentTimestamp'))}';
                                                      print(authToken);
                                                      final url =
                                                          '$baseUrl/payment/web_redirect_to_pay_fee?auth=$authToken&unique_id=academylaravelbycreativeitem';
                                                      print(url);
                                                      // _launchURL(url);

                                                      if (await canLaunchUrl(
                                                          Uri.parse(url))) {
                                                        await launchUrl(
                                                          Uri.parse(url),
                                                          mode: LaunchMode
                                                              .externalApplication,
                                                        );
                                                      } else {
                                                        throw 'Could not launch $url';
                                                      }
                                                    },
                                                    color: kDefaultColor,
                                                    height: 45,
                                                    minWidth: 111,
                                                    textColor: Colors.white,
                                                    shape:
                                                        RoundedRectangleBorder(
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              13.0),
                                                      side: const BorderSide(
                                                          color: kDefaultColor),
                                                    ),
                                                    child: const Text(
                                                      'Checkout',
                                                      style: TextStyle(
                                                        fontWeight:
                                                            FontWeight.w500,
                                                        fontSize: 13,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ),
                            ],
                          );
                        });
                      }
                    }
                  }),
            ),
          ),
        ),
      ),
    );
  }
}
