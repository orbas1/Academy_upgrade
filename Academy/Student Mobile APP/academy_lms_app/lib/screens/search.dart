import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';

import '../constants.dart';
import '../providers/auth.dart';
import '../providers/search_results.dart';
import '../providers/search_visibility.dart';
import '../widgets/custom_text.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {

  int _selectedPageIndex = 0;
  final _keywordController = TextEditingController();

  @override
  void initState() {
    super.initState();
  }

  Future<void> _performSearch(BuildContext context) async {
    final query = _keywordController.text.trim();
    if (query.isEmpty) {
      await context.read<SearchResultsProvider>().search(
            query: '',
            index: 'posts',
            visibilityToken: const SearchVisibilityToken(
              token: '',
              filters: <String>[],
              issuedAt: DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
              expiresAt: DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
            ),
          );
      return;
    }

    final visibilityProvider = context.read<SearchVisibilityProvider>();
    final authToken = context.read<Auth>().token;

    if (visibilityProvider.token == null || visibilityProvider.token!.isExpired) {
      await visibilityProvider.refreshToken();
    }

    final visibilityToken = visibilityProvider.token;

    if (visibilityToken == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Unable to fetch search visibility token.')),
      );
      return;
    }

    await context.read<SearchResultsProvider>().search(
          query: query,
          index: 'posts',
          visibilityToken: visibilityToken,
          authToken: authToken,
        );
  }

  void _selectPage(int index) {
    setState(() {
      _selectedPageIndex = index;
    });
  }

  InputDecoration getInputDecoration(String hintext, VoidCallback onSubmit) {
    return InputDecoration(
      enabledBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      border: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      prefixIcon: const Icon(
        Icons.search,
        color: kGreyLightColor,
      ),
      suffixIcon: IconButton(
        icon: const Icon(Icons.arrow_circle_right),
        onPressed: onSubmit,
      ),
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20.0),
            child: Column(
              children: [
                const SizedBox(height: 80),
                SizedBox(
                  height: MediaQuery.of(context).size.height * .1,
                  child: TextFormField(
                    style: const TextStyle(fontSize: 14),
                    decoration: getInputDecoration(
                      'Search for communities or posts',
                      () => _performSearch(context),
                    ),
                    controller: _keywordController,
                    keyboardType: TextInputType.text,
                    onFieldSubmitted: (_) => _performSearch(context),
                  ),
                ),
                const SizedBox(height: 24),
                Consumer<SearchResultsProvider>(
                  builder: (context, provider, child) {
                    if (provider.isLoading) {
                      return const Padding(
                        padding: EdgeInsets.symmetric(vertical: 24),
                        child: CircularProgressIndicator(),
                      );
                    }

                    if (provider.errorMessage != null) {
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 24),
                        child: Text(
                          provider.errorMessage!,
                          style: const TextStyle(color: Colors.redAccent),
                        ),
                      );
                    }

                    final hits = provider.hits;

                    if (hits.isEmpty) {
                      return const CustomText(
                        text: 'Type in search bar and press enter to search',
                        colors: kGreyLightColor,
                        fontSize: 18,
                        fontWeight: FontWeight.w400,
                      );
                    }

                    return ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: hits.length,
                      separatorBuilder: (_, __) => const Divider(),
                      itemBuilder: (ctx, index) {
                        final hit = hits[index];
                        final title = hit['title'] ?? hit['name'] ?? hit['slug'] ?? 'Result';
                        final subtitle = hit['excerpt'] ?? hit['tagline'] ?? hit['body'];

                        return ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                          title: Text(
                            title.toString(),
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: subtitle != null
                              ? Text(
                                  subtitle.toString(),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                )
                              : null,
                          trailing: Text(
                            provider.response?.index ?? 'posts',
                            style: const TextStyle(fontSize: 12, color: kGreyLightColor),
                          ),
                        );
                      },
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        onTap: _selectPage,
        items: const [
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.school_outlined),
            activeIcon: Icon(Icons.school),
            label: 'My Course',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.favorite_border),
            activeIcon: Icon(Icons.favorite),
            label: 'Wishlist',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.account_circle_outlined),
            activeIcon: Icon(Icons.account_circle),
            label: 'Account',
          ),
        ],
        backgroundColor: Colors.white,
        unselectedItemColor: kSecondaryColor,
        selectedItemColor: kSelectItemColor,
        currentIndex: _selectedPageIndex,
        type: BottomNavigationBarType.fixed,
      ),
    );
  }
}
// https://freewn.com/freewn/full-marks-hidden-marriage-pick-up-a-son-get-a-free-husband/chapter-1403