"use strict";
import { createEl } from "../core/utils.js";
import { fetchVoteSummary, fetchEffectiveVote, submitVote } from "../core/api.js";
import { createVotesZone, renderVoteSummary, setSelected } from "./votes.js";
import { hydrateFullContext } from "../main.js"; // Optional: To update topic score? Or just independent?

export function injectPostVotes() {
    // Find all Post Containers (SMF 2.0 - bitcointalk)
    // Usually <td class="td_headerandpost">
    const posts = document.querySelectorAll("td.td_headerandpost");

    if (!posts.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const { zone, postId } = entry.target._spPostData;
                hydratePost(zone, postId);
                observer.unobserve(entry.target);
            }
        });
    });

    posts.forEach(td => {
        // Avoid double injection
        if (td.querySelector(".sp-zone-votes")) return;

        // Extract Post ID
        // Look for <form action"...;msg=123"> or anchor <a name="msg123">
        // Best: Look for "Subject:" link?
        const subjectLink = td.querySelector("div[id^='subject_'] a");
        let postId = 0;

        if (subjectLink && subjectLink.href) {
            const match = subjectLink.href.match(/msg(\d+)/);
            if (match) postId = match[1];
        }

        if (!postId) {
            // Fallback: Check anchor
            const anchor = td.previousElementSibling; // td.windowbg?
            // SMF structure is weird.
            // Let's look for "Modify" button link?
            // Standard: <div class="smalltext">... link to msg ...</div>
            // For now, if we can't find ID, skip.
            return;
        }

        // Create Zone
        const onVote = async (val) => {
            const res = await submitVote(val, { voteCategory: 'post', targetId: postId });
            if (res.ok) {
                renderVoteSummary(summaryEl, {
                    vote_count: res.vote_count,
                    rank: res.rank
                }, { kind: 'post' });
                setSelected(buttons, val, val); // Assume effective = desired
            }
        };

        const { zone, buttons, summaryEl } = createVotesZone(onVote, { kind: 'post', targetId: postId });

        // Inject (Top Right of Message?)
        // SMF: td.td_headerandpost > div (Subject) ... div.post
        // Check if we can float it or place it.
        // Try Prepending to the first DIV (Subject line)
        if (td.firstChild) {
            td.insertBefore(zone, td.firstChild);
        } else {
            td.appendChild(zone);
        }

        // Observe for Hydration
        zone._spPostData = { zone, postId, buttons, summaryEl };
        observer.observe(zone);
    });
}

async function hydratePost(zone, postId) {
    const { buttons, summaryEl } = zone._spPostData;

    // Parallel Fetch
    const [summary, effective] = await Promise.all([
        fetchVoteSummary({ voteCategory: 'post', targetId: postId }),
        fetchEffectiveVote({ voteCategory: 'post', targetId: postId })
    ]);

    if (summary) {
        renderVoteSummary(summaryEl, summary, { kind: 'post' });
    }

    setSelected(buttons, effective, null);
}
